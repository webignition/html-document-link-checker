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

    /**
     * @param Configuration $configuration
     * @param HttpClient $httpClient
     */
    public function __construct(Configuration $configuration, HttpClient $httpClient)
    {
        $this->configuration = $configuration;
        $this->httpClient = $httpClient;
        $this->urlHealthChecker = new UrlHealthChecker();
        $this->urlHealthChecker->setHttpClient($httpClient);
        $this->urlHealthChecker->setConfiguration($configuration->getUrlHealthCheckerConfiguration());
    }

    /**
     * @param WebPage $webPage
     */
    public function setWebPage(WebPage $webPage)
    {
        $this->webPage = $webPage;
        $this->linkCheckResults = null;
    }

    /**
     * @return LinkResult[]
     *
     * @throws QueryPathException
     *
     * @throws GuzzleException
     */
    public function getAll()
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
     * @return array
     *
     * @throws QueryPathException
     * @throws GuzzleException
     */
    public function getErrored()
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

    /**
     *
     * @param LinkState $linkState
     *
     * @return bool
     */
    private function isErrored(LinkState $linkState)
    {
        if ($linkState->getType() == LinkState::TYPE_CURL) {
            return true;
        }

        if ($this->isHttpErrorStatusCode($linkState->getState())) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     *
     * @throws QueryPathException
     * @throws GuzzleException
     */
    public function getWorking()
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
     * @param int $statusCode
     *
     * @return bool
     */
    private function isHttpErrorStatusCode($statusCode)
    {
        return $statusCode >= 300;
    }


    /**
     * @param string $url
     *
     * @return LinkState
     *
     * @throws GuzzleException
     */
    private function getLinkState($url)
    {
        $comparisonUrl = $this->getComparisonUrl($url);

        if ($this->hasLinkStateForUrl($url)) {
            return $this->urlToLinkStateMap[$comparisonUrl];
        }

        $linkState = $this->deriveLinkState($url);

        if (!$this->isErrored($linkState)) {
            $this->urlToLinkStateMap[$comparisonUrl] = $linkState;
        }

        return $linkState;
    }

    /**
     * @param string $url
     *
     * @return LinkState
     *
     * @throws GuzzleException
     */
    private function deriveLinkState($url)
    {
        return $this->urlHealthChecker->check($url);
    }


    /**
     * @param string $url
     *
     * @return bool
     */
    private function hasLinkStateForUrl($url)
    {
        return isset($this->urlToLinkStateMap[$this->getComparisonUrl($url)]);
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function getComparisonUrl($url)
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


    /**
     * @param string $url
     *
     * @return bool
     */
    private function isUrlToBeIncluded($url)
    {
        $urlObject = new NormalisedUrl($url);
        if (!$this->isUrlSchemeToBeIncluded($urlObject)) {
            return false;
        }

        if (in_array($url, $this->configuration->getUrlsToExclude())) {
            return false;
        }

        if (!$this->isUrlDomainToBeIncluded($urlObject)) {
            return false;
        }

        return true;
    }

    /**
     * @param NormalisedUrl $url
     *
     * @return bool
     */
    private function isUrlSchemeToBeIncluded(NormalisedUrl $url)
    {
        return !in_array($url->getScheme(), $this->configuration->getSchemesToExclude());
    }

    /**
     * @param NormalisedUrl $url
     *
     * @return bool
     */
    private function isUrlDomainToBeIncluded(NormalisedUrl $url)
    {
        return !in_array($url->getHost(), $this->configuration->getDomainsToExclude());
    }
}
