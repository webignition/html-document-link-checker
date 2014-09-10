<?php
namespace webignition\HtmlDocument\LinkChecker;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\TooManyRedirectsException;
use Guzzle\Plugin\History\HistoryPlugin;
use webignition\Cookie\UrlMatcher\UrlMatcher;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;
use webignition\NormalisedUrl\NormalisedUrl;
use webignition\WebResource\WebPage\WebPage;
use Guzzle\Http\Message\Request as GuzzleRequest;
use Guzzle\Http\Message\Response as GuzzleResponse;
use Guzzle\Http\Url as GuzzleUrl;

class LinkChecker {
    
    const HTTP_STATUS_CODE_OK = 200;
    const HTTP_STATUS_CODE_METHOD_NOT_ALLOWED = 405;
    const HTTP_STATUS_CODE_NOT_IMPLEMENTED = 501;
    
    const CURL_MALFORMED_URL_CODE = 3;
    const CURL_MALFORMED_URL_MESSAGE = 'The URL was not properly formatted.';   
    
    const BAD_REQUEST_LIMIT = 3;
    
    
    /**
     *
     * @var WebPage
     */
    private $webPage = null;
    
    
    /**
     *
     * @var array
     */
    private $linkCheckResults = null;
    
    
    /**
     *
     * @var array
     */
    private $urlToLinkStateMap = array();
    
    
    /**
     *
     * @var array
     */
    private $badRequestCount = 0;
    
    
    /**
     *
     * @var Configuration
     */
    private $configuration;
    
    
    /**
     * 
     * @return Configuration
     */
    public function getConfiguration() {
        if (is_null($this->configuration)) {
            $this->configuration = new Configuration();
        }
        
        return $this->configuration;
    }
    
    
    /**
     * 
     * @return HistoryPlugin
     */
    private function getHttpClientHistory() {
        $requestSentListeners = $this->getConfiguration()->getBaseRequest()->getEventDispatcher()->getListeners('request.sent');
        foreach ($requestSentListeners as $requestSentListener) {
            if ($requestSentListener[0] instanceof HistoryPlugin) {
                return $requestSentListener[0];
            }
        }
        
        return null;
    }
    
    
    /**
     * 
     * @param WebPage $webPage
     */
    public function setWebPage(WebPage $webPage) {
        $this->webPage = $webPage;
        $this->linkCheckResults = null;        
    }
    
    
    /**
     * 
     * @return array
     */
    public function getAll() {
        if (is_null($this->linkCheckResults)) {
            $this->linkCheckResults = array();            
            
            if (is_null($this->webPage)) {
                return $this->linkCheckResults;
            }

            $linkFinder = new HtmlDocumentLinkUrlFinder();
            $linkFinder->getConfiguration()->setSourceUrl($this->webPage->getHttpResponse()->getEffectiveUrl());
            $linkFinder->getConfiguration()->setSourceContent($this->webPage->getHttpResponse()->getBody(true));

            if (!$linkFinder->hasUrls()) {
                return $this->linkCheckResults;          
            }

            foreach ($linkFinder->getAll() as $link) {
                $link['url'] = rawurldecode($link['url']);

                if ($this->isUrlToBeIncluded($link['url'])) {
                    $this->linkCheckResults[] = new LinkResult($link['url'], $link['element'], $this->getLinkState($link['url']));
                }
            }
        }
        
        return $this->linkCheckResults;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getErrored() {
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
     * @param \webignition\HtmlDocument\LinkChecker\LinkState $linkState
     * @return boolean
     */
    private function isErrored(LinkState $linkState) {        
        if ($linkState->getType() == LinkState::TYPE_CURL) {
            return true;
        }
        
        if ($linkState->getType() == LinkState::TYPE_HTTP && $this->isHttpErrorStatusCode($linkState->getState())) {
            return true;
        }
        
        return false;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getWorking() {
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
     * 
     * @param int $statusCode
     * @return boolean
     */
    private function isHttpErrorStatusCode($statusCode) {        
        return in_array(substr((string)$statusCode, 0, 1), array('3', '4', '5'));
    }
    
    
    /**
     * 
     * @param string $url
     * @return \webignition\HtmlDocument\LinkChecker\LinkState
     */
    private function getLinkState($url) {        
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
     * 
     * @param string $url
     * @return \webignition\HtmlDocument\LinkChecker\LinkState
     */
    private function deriveLinkState($url) {
        $requests = $this->buildRequestSet($url);
        
        try {
            foreach ($requests as $request) {               
                $response = $this->getHttpResponse($request);
                
                if ($response->getStatusCode() === self::HTTP_STATUS_CODE_OK) {
                    return new LinkState(LinkState::TYPE_HTTP, $response->getStatusCode());
                }
            }           
        } catch (CurlException $curlException) {
            return new LinkState(LinkState::TYPE_CURL, $curlException->getErrorNo());
        }
        
        return new LinkState(LinkState::TYPE_HTTP, $response->getStatusCode());
    }
    
    
    /**
     * 
     * @param string $url
     * @return \Guzzle\Http\Message\Request[]
     */
    private function buildRequestSet($url) {        
        $useEncodingOptions = ($this->getConfiguration()->getToggleUrlEncoding())
            ? array(true, false)
            : array(true);
        
        $requests = array();
        
        $userAgentSelection = $this->getConfiguration()->getUserAgentSelectionForRequest();
        
        foreach ($userAgentSelection as $userAgent) {
            foreach ($this->getConfiguration()->getHttpMethodList() as $methodIndex => $method) {
                foreach ($useEncodingOptions as $useEncoding) {
                    $requestUrl = GuzzleUrl::factory($url);
                    $requestUrl->getQuery()->useUrlEncoding($useEncoding);
                    
                    $request = clone $this->getConfiguration()->getBaseRequest();
                    $request->setUrl($requestUrl);
                    $request->setHeader('user-agent', $userAgent);                    
                    $request->setHeader('Referer', $this->webPage->getHttpResponse()->getEffectiveUrl());
                    
                    $this->setRequestCookies($request);
                    
                    $requests[] = $request;
                }
            }
        }
        
        return $requests;       
    }   
    
    private function setRequestCookies(GuzzleRequest $request) {
        if (!is_null($request->getCookies())) {
            foreach ($request->getCookies() as $name => $value) {
                $request->removeCookie($name);
            }
        }
        
        
        $cookieUrlMatcher = new UrlMatcher();
        
        foreach ($this->getConfiguration()->getCookies() as $cookie) {
            if ($cookieUrlMatcher->isMatch($cookie, $request->getUrl())) {
                $request->addCookie($cookie['name'], $cookie['value']);
            }
        } 
    }
    
    
    /**
     * 
     * @param string $url
     * @return boolean
     */
    private function hasLinkStateForUrl($url) {
        return isset($this->urlToLinkStateMap[$this->getComparisonUrl($url)]);
    }


    /**
     * @param string $url
     * @return string
     */
    private function getComparisonUrl($url) {
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
     * @param GuzzleRequest $request
     * @return GuzzleResponse|null
     * @throws \Guzzle\Http\Exception\CurlException
     */
    private function getHttpResponse(GuzzleRequest $request) {
        try {                
            return $request->send();
        } catch (TooManyRedirectsException $tooManyRedirectsException) {
            return $this->getHttpClientHistory()->getLastResponse();
        } catch (BadResponseException $badResponseException) {
            $this->badRequestCount++;
            
            if ($this->isBadRequestLimitReached()) {                    
                return $badResponseException->getResponse();
            }
            
            return $this->getHttpResponse($request);
        } catch (InvalidArgumentException $e) {
            if (substr_count($e->getMessage(), 'unable to parse malformed url')) {
                $curlException = $this->getCurlMalformedUrlException();
                throw $curlException;
            }
        }         
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function isBadRequestLimitReached() {
        if ($this->getConfiguration()->getRetryOnBadResponse() === false) {
            return true;
        }
        
        return $this->badRequestCount > self::BAD_REQUEST_LIMIT - 1;        
    }
    
    
    /**
     * 
     * @return CurlException
     */
    private function getCurlMalformedUrlException() {
        $curlException = new CurlException();
        $curlException->setError(self::CURL_MALFORMED_URL_MESSAGE, self::CURL_MALFORMED_URL_CODE);
        return $curlException;
    }

    
    
    /**
     * 
     * @param string $url
     * @return boolean
     */
    private function isUrlToBeIncluded($url) {        
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
     * 
     * @param NormalisedUrl $url
     * @return boolean
     */
    private function isUrlSchemeToBeIncluded(NormalisedUrl $url) {
        return !in_array($url->getScheme(), $this->getConfiguration()->getSchemesToExclude());
    }
    
    
    /**
     * 
     * @param NormalisedUrl $url
     * @return boolean
     */
    private function isUrlDomainToBeIncluded(NormalisedUrl $url) {
        return !in_array($url->getHost(), $this->getConfiguration()->getDomainsToExclude());
    }
    
}