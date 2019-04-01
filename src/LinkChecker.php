<?php

namespace webignition\HtmlDocument\LinkChecker;

use GuzzleHttp\Client as HttpClient;
use webignition\NormalisedUrl\NormalisedUrl;
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

    public function getLinkState(string $url): ?LinkState
    {
        $comparisonUrl = $this->createComparisonUrl($url);

        if (!$this->isUrlToBeIncluded($comparisonUrl)) {
            return null;
        }

        $hasLinkStateForUrl = isset($this->urlToLinkStateMap[$comparisonUrl]);

        if ($hasLinkStateForUrl) {
            return $this->urlToLinkStateMap[$comparisonUrl];
        }

        $linkState = $this->urlHealthChecker->check($url);

        if (!$linkState->isError()) {
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

        $urlObject->setFragment('');

        return (string)$urlObject;
    }

    private function isUrlToBeIncluded(string $url): bool
    {
        $urlObject = new NormalisedUrl($url);

        $isUrlSchemeExcluded = in_array($urlObject->getScheme(), $this->configuration->getSchemesToExclude());
        $isUrlExcluded = in_array($url, $this->configuration->getUrlsToExclude());
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
