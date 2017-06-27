<?php
namespace webignition\HtmlDocument\LinkChecker;

use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkFinderConfiguration;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;
use webignition\NormalisedUrl\NormalisedUrl;
use webignition\UrlHealthChecker\Configuration as UrlHealthCheckerConfiguration;
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
    private $urlToLinkStateMap = array();

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var UrlHealthChecker
     */
    private $urlHealthChecker = null;

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        if (is_null($this->configuration)) {
            $this->configuration = new Configuration();
        }

        return $this->configuration;
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
     */
    public function getAll()
    {
        if (is_null($this->linkCheckResults)) {
            $this->linkCheckResults = array();

            if (is_null($this->webPage)) {
                return $this->linkCheckResults;
            }

            $linkFinderConfiguration = new LinkFinderConfiguration([
                LinkFinderConfiguration::CONFIG_KEY_SOURCE => $this->webPage,
                LinkFinderConfiguration::CONFIG_KEY_SOURCE_URL => $this->webPage->getHttpResponse()->getEffectiveUrl(),
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
     */
    public function getErrored()
    {
        $links = array();
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
     * @return boolean
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
     */
    public function getWorking()
    {
        $links = array();
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
     * @return boolean
     */
    private function isHttpErrorStatusCode($statusCode)
    {
        if (in_array(substr((string)$statusCode, 0, 1), array('3', '4', '5'))) {
            return true;
        }

        if ($statusCode == 999) {
            return true;
        }

        return false;
    }


    /**
     * @param string $url
     *
     * @return LinkState
     */
    private function getLinkState($url)
    {
        if ($this->hasLinkStateForUrl($url)) {
            return $this->urlToLinkStateMap[$this->getComparisonUrl($url)];
        }

        $linkState = $this->deriveLinkState($url);

        if (!$this->isErrored($linkState)) {
            $this->urlToLinkStateMap[$this->getComparisonUrl($url)] = $linkState;
        }

        return $linkState;
    }

    /**
     * @param string $url
     *
     * @return LinkState
     */
    private function deriveLinkState($url)
    {
        return $this->getUrlHealthChecker()->check($url);
    }


    /**
     * @param string $url
     *
     * @return boolean
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
        if (!$this->getConfiguration()->ignoreFragmentInUrlComparison()) {
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
     * @return boolean
     */
    private function isUrlToBeIncluded($url)
    {
        $urlObject = new NormalisedUrl($url);
        if (!$this->isUrlSchemeToBeIncluded($urlObject)) {
            return false;
        }

        if (in_array($url, $this->getConfiguration()->getUrlsToExclude())) {
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
     * @return boolean
     */
    private function isUrlSchemeToBeIncluded(NormalisedUrl $url)
    {
        return !in_array($url->getScheme(), $this->getConfiguration()->getSchemesToExclude());
    }

    /**
     * @param NormalisedUrl $url
     *
     * @return boolean
     */
    private function isUrlDomainToBeIncluded(NormalisedUrl $url)
    {
        return !in_array($url->getHost(), $this->getConfiguration()->getDomainsToExclude());
    }

    /**
     * @return UrlHealthChecker
     */
    public function getUrlHealthChecker()
    {
        if (is_null($this->urlHealthChecker)) {
            $this->urlHealthChecker = new UrlHealthChecker();
            $this->urlHealthChecker->setConfiguration(new UrlHealthCheckerConfiguration([
                UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_CLIENT => $this->getConfiguration()->getHttpClient(),
            ]));
        }

        return $this->urlHealthChecker;
    }
}
