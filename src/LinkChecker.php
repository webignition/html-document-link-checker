<?php

namespace webignition\HtmlDocument\LinkChecker;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use QueryPath\Exception as QueryPathException;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkFinderConfiguration;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;
use webignition\NormalisedUrl\NormalisedUrl;
use webignition\WebResource\WebPage\WebPage;
use webignition\UrlHealthChecker\UrlHealthChecker;
use webignition\UrlHealthChecker\LinkState;

class LinkChecker
{
    const HTTP_STATUS_CODE_OK = 200;
    const HTTP_STATUS_CODE_METHOD_NOT_ALLOWED = 405;
    const HTTP_STATUS_CODE_NOT_IMPLEMENTED = 501;

    const CURL_MALFORMED_URL_CODE = 3;
    const CURL_MALFORMED_URL_MESSAGE = 'The URL was not properly formatted.';

    const BAD_REQUEST_LIMIT = 3;

    /**
     * @var WebPage
     */
    private $webPage = null;

    /**
     * @var LinkResult[]
     */
    private $linkCheckResults = null;

    /**
     * @var array
     */
    private $urlToLinkStateMap = [];

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var UrlHealthChecker
     */
    private $urlHealthChecker = null;

    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct(Configuration $configuration, HttpClient $httpClient)
    {
        $this->configuration = $configuration;
        $this->httpClient = $httpClient;
        $this->urlHealthChecker = new UrlHealthChecker();
        $this->urlHealthChecker->setHttpClient($httpClient);
        $this->urlHealthChecker->setConfiguration($configuration->getUrlHealthCheckerConfiguration());
    }

    public function setWebPage(WebPage $webPage)
    {
        $this->webPage = $webPage;
        $this->linkCheckResults = null;
    }

    /**
     * @return LinkResult[]
     *
     * @throws QueryPathException
     * @throws GuzzleException
     */
    public function getAll(): array
    {
        if (empty($this->webPage)) {
            return [];
        }

        if (is_null($this->linkCheckResults)) {
            $this->linkCheckResults = [];

            $linkFinderConfiguration = new LinkFinderConfiguration([
                LinkFinderConfiguration::CONFIG_KEY_SOURCE => $this->webPage,
                LinkFinderConfiguration::CONFIG_KEY_SOURCE_URL => (string)$this->webPage->getUri(),
            ]);

            $linkFinder = new HtmlDocumentLinkUrlFinder();
            $linkFinder->setConfiguration($linkFinderConfiguration);

            if (!$linkFinder->hasUrls()) {
                return $this->linkCheckResults;
            }

            foreach ($linkFinder->getAll() as $link) {
                $link['url'] = rawurldecode($link['url']);

                if ($this->isUrlToBeIncluded($link['url'])) {
                    $this->linkCheckResults[] = new LinkResult(
                        $link['url'],
                        $link['element'],
                        $this->getLinkState($link['url'])
                    );
                }
            }
        }

        return $this->linkCheckResults;
    }

    /**
     * @return LinkResult[]
     *
     * @throws QueryPathException
     * @throws GuzzleException
     */
    public function getErrored(): array
    {
        $links = [];
        foreach ($this->getAll() as $linkCheckResult) {
            /* @var $linkCheckResult LinkResult */
            if ($this->isErrored($linkCheckResult->getLinkState())) {
                $links[] = $linkCheckResult;
            }
        }

        return $links;
    }

    private function isErrored(LinkState $linkState): bool
    {
        if ($linkState->getType() == LinkState::TYPE_CURL) {
            return true;
        }

        if ($linkState->getState() >= 300) {
            return true;
        }

        return false;
    }

    /**
     * @return LinkResult[]
     *
     * @throws QueryPathException
     * @throws GuzzleException
     */
    public function getWorking(): array
    {
        $links = [];
        foreach ($this->getAll() as $linkCheckResult) {
            /* @var $linkCheckResult LinkResult */
            if (!$this->isErrored($linkCheckResult->getLinkState())) {
                $links[] = $linkCheckResult;
            }
        }

        return $links;
    }

    /**
     * @param string $url
     *
     * @return LinkState
     * @throws GuzzleException
     */
    private function getLinkState(string $url): LinkState
    {
        $comparisonUrl = $this->createComparisonUrl($url);
        $hasLinkStateForUrl = isset($this->urlToLinkStateMap[$comparisonUrl]);

        if ($hasLinkStateForUrl) {
            return $this->urlToLinkStateMap[$comparisonUrl];
        }

        $linkState = $this->urlHealthChecker->check($url);

        if (!$this->isErrored($linkState)) {
            $this->urlToLinkStateMap[$comparisonUrl] = $linkState;
        }

        return $linkState;
    }

    private function createComparisonUrl(string $url): string
    {
        if (false === $this->configuration->getIgnoreFragmentInUrlComparison()) {
            return $url;
        }

        $urlObject = new NormalisedUrl($url);
        if (!$urlObject->hasFragment()) {
            return $url;
        }

        $urlObject->setFragment(null);

        return (string)$urlObject;
    }

    private function isUrlToBeIncluded(string $url): bool
    {
        $urlObject = new NormalisedUrl($url);

        $isUrlSchemeExcluded = in_array($urlObject->getScheme(), $this->configuration->getSchemesToExclude());
        $isUrlExcluded = in_array((string)$url, $this->configuration->getUrlsToExclude());
        $isUrlDomainExcluded = in_array($urlObject->getHost(), $this->configuration->getDomainsToExclude());

        if ($isUrlSchemeExcluded) {
            return false;
        }

        if ($isUrlExcluded) {
            return false;
        }

        if ($isUrlDomainExcluded) {
            return false;
        }

        return true;
    }
}
