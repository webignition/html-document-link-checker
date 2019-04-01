<?php

namespace webignition\HtmlDocument\LinkChecker;

use webignition\Uri\Normalizer;
use webignition\Uri\Uri;
use webignition\UrlHealthChecker\UrlHealthChecker;
use webignition\UrlHealthChecker\LinkState;

class LinkChecker
{
    /**
     * @var array
     */
    private $urlToLinkStateMap = [];

    /**
     * @var UrlHealthChecker
     */
    private $urlHealthChecker = null;

    public function __construct(UrlHealthChecker $urlHealthChecker)
    {
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
        $uri = new Uri($url);
        $uri = Normalizer::normalize($uri);
        $uri = $uri->withFragment('');

        return (string) $uri;
    }
}
