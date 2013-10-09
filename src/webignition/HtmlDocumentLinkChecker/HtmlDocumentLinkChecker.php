<?php
namespace webignition\HtmlDocumentLinkChecker;

class HtmlDocumentLinkChecker {           
    
    const URL_SCHEME_MAILTO = 'mailto';
    const URL_ABOUT_ABOUT = 'about';
    const HTTP_STATUS_CODE_METHOD_NOT_ALLOWED = 405;
    const HTTP_STATUS_CODE_NOT_IMPLEMENTED = 501;
    
    const HTTP_METHOD_HEAD = 'HEAD';
    const HTTP_METHOD_GET = 'GET';
    

    
    
    /**
     *
     * @var array
     */
    private $httpMethodList = array(
        self::HTTP_METHOD_HEAD,
        self::HTTP_METHOD_GET
    );
    
    
    /**
     *
     * @var array
     */
    private $schemesToExclude = array(
        self::URL_SCHEME_MAILTO,
        self::URL_ABOUT_ABOUT
    );
    
    
    /**
     *
     * @var \Guzzle\Http\Client 
     */
    private $httpClient = null;
    
    
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
    private $urlsToExclude = array();
    
    
    /**
     * 
     * @param array $httpMethodList
     */
    public function setHttpMethodList($httpMethodList) {
        $this->httpMethodList = $httpMethodList;
    }
    
    
    /**
     * 
     * @param \Guzzle\Http\Client $httpClient
     */
    public function setHttpClient(\Guzzle\Http\Client $httpClient) {
        $this->httpClient = $httpClient;
    }
    
    
    /**
     * 
     * @return \Guzzle\Http\Client
     */
    public function getHttpClient() {
        if (is_null($this->httpClient)) {
            $this->httpClient = new \Guzzle\Http\Client();
        }
        
        if (is_null($this->getHttpClientHistory())) {
            $this->httpClient->addSubscriber(new \Guzzle\Plugin\History\HistoryPlugin());
        }
        
        return $this->httpClient;
    }
    
    
    /**
     * 
     * @return \Guzzle\Plugin\History\HistoryPlugin
     */
    private function getHttpClientHistory() {
        $requestSentListeners = $this->httpClient->getEventDispatcher()->getListeners('request.sent');
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
     * @param array $urlsToExclude
     */
    public function setUrlsToExclude($urlsToExclude) {
        $this->urlsToExclude = $urlsToExclude;
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
                if ($this->isUrlToBeIncluded($link['url'])) {
                    $this->linkCheckResults[] = new LinkCheckResult($link['url'], $link['element'], $this->getLinkState($link['url']));
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
            if ($this->isErrored($linkCheckResult)) {
                $links[] = $linkCheckResult;
            }
        }
        
        return $links;
    }
    
    
    /**
     * 
     * @param \webignition\HtmlDocumentLinkChecker\LinkCheckResult $linkCheckResult
     * @return boolean
     */
    private function isErrored(LinkCheckResult $linkCheckResult) {        
        if ($linkCheckResult->getLinkState()->getType() == LinkState::TYPE_CURL) {
            return true;
        }
        
        if ($linkCheckResult->getLinkState()->getType() == LinkState::TYPE_HTTP && $this->isHttpErrorStatusCode($linkCheckResult->getLinkState()->getState())) {
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
            if (!$this->isErrored($linkCheckResult)) {
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
     * @return \webignition\HtmlDocumentLinkChecker\LinkState
     */
    private function getLinkState($url) {
        if (!$this->hasLinkStateForUrl($url)) {
            $linkState = $this->deriveLinkState($url);            
            $this->urlToLinkStateMap[$url] = $linkState;
        }
        
        return $this->urlToLinkStateMap[$url];      
    }
    
    
    /**
     * 
     * @param string $url
     * @return \webignition\HtmlDocumentLinkChecker\LinkState
     */
    private function deriveLinkState($url) {        
        try {
            foreach ($this->httpMethodList as $methodIndex => $method) {
                $isLastMethod = $methodIndex == count($this->httpMethodList) - 1;            
                $response = $this->getResponseForHttpMethod($url, $method);

                if (!$response->isError() || $isLastMethod) {
                    return new LinkState(LinkState::TYPE_HTTP, $response->getStatusCode());
                }
            }            
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {
            return new LinkState(LinkState::TYPE_CURL, $curlException->getErrorNo());
        }
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
     * @param string $url
     * @param string $method
     * @return \Guzzle\Http\Message\Response
     */
    private function getResponseForHttpMethod($url, $method) {
        try {
            $request = $this->getHttpClient()->createRequest($method, $url);
            $response = $request->send();
        } catch (\Guzzle\Http\Exception\TooManyRedirectsException $tooManyRedirectsException) {
            $response = $this->getHttpClientHistory()->getLastResponse();
        } catch (\Guzzle\Http\Exception\BadResponseException $badResponseException) {
            $response = $badResponseException->getResponse();                            
        }
        
        return $response;      
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
        
        if (in_array($url, $this->urlsToExclude)) {
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
        return !in_array($url->getScheme(), $this->schemesToExclude);
    }
    
}