<?php

namespace webignition\HtmlDocument\LinkChecker;

use webignition\Uri\Normalizer;
use webignition\Uri\Uri;
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

    public function __construct(Configuration $configuration, UrlHealthChecker $urlHealthChecker)
    {
        $this->configuration = $configuration;
        $this->urlHealthChecker = $urlHealthChecker;
    }

    public function getLinkState(string $url): ?LinkState
    {
        $comparisonUrl = $this->createComparisonUrl($url);

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

        $uri = new Uri($url);
        $uri = Normalizer::normalize($uri);

        if (empty(trim($uri->getFragment()))) {
            return $url;
        }

        $uri = $uri->withFragment('');

        return (string) $uri;
    }
}
