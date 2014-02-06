<?php
namespace webignition\HtmlDocument\LinkChecker;

use webignition\HtmlDocument\LinkChecker\Configuration;

class LinkChecker {           
    
    const HTTP_STATUS_CODE_OK = 200;
    const HTTP_STATUS_CODE_METHOD_NOT_ALLOWED = 405;
    const HTTP_STATUS_CODE_NOT_IMPLEMENTED = 501;
    
    const CURL_MALFORMED_URL_CODE = 3;
    const CURL_MALFORMED_URL_MESSAGE = 'The URL was not properly formatted.';   
    
    const BAD_REQUEST_LIMIT = 3;
    
    
    /**
     *
     * @var \webignition\WebResource\WebPage\WebPage
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
     * @var \webignition\HtmlDocument\LinkChecker\Configuration 
     */
    private $configuration;
    
    
    /**
     * 
     * @return \webignition\HtmlDocument\LinkChecker\Configuration
     */
    public function getConfiguration() {
        if (is_null($this->configuration)) {
            $this->configuration = new Configuration();
        }
        
        return $this->configuration;
    }
    
    
    /**
     * 
     * @return \Guzzle\Plugin\History\HistoryPlugin
     */
    private function getHttpClientHistory() {
        $requestSentListeners = $this->getConfiguration()->getBaseRequest()->getEventDispatcher()->getListeners('request.sent');
        foreach ($requestSentListeners as $requestSentListener) {
            if ($requestSentListener[0] instanceof \Guzzle\Plugin\History\HistoryPlugin) {
                return $requestSentListener[0];
            }
        }
        
        return null;
    }
    
    
    /**
     * 
     * @param \webignition\WebResource\WebPage\WebPage $webPage
     */
    public function setWebPage(\webignition\WebResource\WebPage\WebPage $webPage) {
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

            $linkFinder = new \webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder();

            $linkFinder->setSourceUrl($this->webPage->getUrl());
            $linkFinder->setSourceContent($this->webPage->getContent());

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
            /* @var $linkCheckResult LinkCheckResult */
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
            /* @var $linkCheckResult LinkCheckResult */
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
    private function isStrictHttpErrorStatusCode($statusCode) {        
        return in_array(substr((string)$statusCode, 0, 1), array('4', '5'));
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
            return $this->urlToLinkStateMap[$url];
        }        

        $linkState = $this->deriveLinkState($url);

        if (!$this->isErrored($linkState)) {
            $this->urlToLinkStateMap[$url] = $linkState;
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
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {
            return new LinkState(LinkState::TYPE_CURL, $curlException->getErrorNo());
        }
        
        return new LinkState(LinkState::TYPE_HTTP, $response->getStatusCode());
    }
    
    
    /**
     * 
     * @param type $url
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
                    $requestUrl = \Guzzle\Http\Url::factory($url);
                    $requestUrl->getQuery()->useUrlEncoding($useEncoding);
                    
                    $request = clone $this->getConfiguration()->getBaseRequest();
                    $request->setUrl($requestUrl);
                    $request->setHeader('user-agent', $userAgent);                    
                    $request->setHeader('Referer', $this->webPage->getUrl());
                    
                    $requests[] = $request;
                }
            }
        }
        
        return $requests;       
    }   
    
    
    /**
     * 
     * @param string $url
     * @return boolean
     */
    private function hasLinkStateForUrl($url) {        
        return isset($this->urlToLinkStateMap[$url]);
    }
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Request $request
     * @return \Guzzle\Http\Message\Response
     */
    private function getHttpResponse(\Guzzle\Http\Message\Request $request) {                                
        try {                
            return $request->send();
        } catch (\Guzzle\Http\Exception\TooManyRedirectsException $tooManyRedirectsException) {
            return $this->getHttpClientHistory()->getLastResponse();
        } catch (\Guzzle\Http\Exception\BadResponseException $badResponseException) {                
            $this->badRequestCount++;
            
            if ($this->isBadRequestLimitReached()) {                    
                return $badResponseException->getResponse();
            }
            
            return $this->getHttpResponse($request);
        } catch (\Guzzle\Common\Exception\InvalidArgumentException $e) {
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
     * @return \Guzzle\Http\Exception\CurlException
     */
    private function getCurlMalformedUrlException() {
        $curlException = new \Guzzle\Http\Exception\CurlException();
        $curlException->setError(self::CURL_MALFORMED_URL_MESSAGE, self::CURL_MALFORMED_URL_CODE);
        return $curlException;
    }

    
    
    /**
     * 
     * @param string $url
     * @return boolean
     */
    private function isUrlToBeIncluded($url) {        
        $urlObject = new \webignition\NormalisedUrl\NormalisedUrl($url);                    
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
     * @param \webignition\NormalisedUrl\NormalisedUrl $url
     * @return boolean
     */
    private function isUrlSchemeToBeIncluded(\webignition\NormalisedUrl\NormalisedUrl $url) {
        return !in_array($url->getScheme(), $this->getConfiguration()->getSchemesToExclude());
    }
    
    
    /**
     * 
     * @param \webignition\NormalisedUrl\NormalisedUrl $url
     * @return boolean
     */
    private function isUrlDomainToBeIncluded(\webignition\NormalisedUrl\NormalisedUrl $url) {
        return !in_array($url->getHost(), $this->getConfiguration()->getDomainsToExclude());
    }
    
}