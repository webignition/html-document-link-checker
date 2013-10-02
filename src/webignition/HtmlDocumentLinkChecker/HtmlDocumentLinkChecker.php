<?php
namespace webignition\HtmlDocumentLinkChecker;

class HtmlDocumentLinkChecker {           
    
    const URL_SCHEME_MAILTO = 'mailto';
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
        self::URL_SCHEME_MAILTO        
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
    private $linkStates = null;
    
    
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
        
        return $this->httpClient;
    }
    
    
    /**
     * 
     * @param \webignition\WebResource\WebPage\WebPage $webPage
     */
    public function setWebPage(\webignition\WebResource\WebPage\WebPage $webPage) {
        $this->webPage = $webPage;
        $this->linkStates = null;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getAll() {
        if (is_null($this->linkStates)) {
            $this->checkLinkStates();
        }
        
        return $this->linkStates;
    } 
    
    
    /**
     * 
     * @param int $statusCode
     * @return array
     */
    public function getByHttpState($statusCode) {
        return $this->getLinksByLinkState(LinkState::TYPE_HTTP, $statusCode);       
    }
    
    /**
     * 
     * @param int $statusCode
     * @return array
     */    
    public function getByCurlState($curlCode) {
        return $this->getLinksByLinkState(LinkState::TYPE_CURL, $curlCode);       
    }
    
    
    /**
     * 
     * @return array
     */
    public function getErrored() {
        $curlStateLinks = $this->getLinksByType(LinkState::TYPE_CURL);
        $httpStateLinks = $this->getLinksByType(LinkState::TYPE_HTTP);

        $links = $curlStateLinks;
        foreach ($httpStateLinks as $linkState) {
            if ($this->isHttpErrorStatusCode($linkState->getState())) {
                $links[] = $linkState;
            }
        }
        
        return $links;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getWorking() {
        $httpStateLinks = $this->getLinksByType(LinkState::TYPE_HTTP);
        
        $links = array();
        foreach ($httpStateLinks as $linkState) {
            if (!$this->isHttpErrorStatusCode($linkState->getState())) {
                $links[] = $linkState;
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
        return in_array(substr((string)$statusCode, 0, 1), array('4', '5'));
    }
    
    
    
    
    /**
     * 
     * @param string $type
     * @return array
     */
    private function getLinksByType($type) {
        $linkStates = $this->getAll();
        $links = array();
        
        foreach ($linkStates as $url => $linkState) {
            if ($linkState->getType() == $type) {
                $links[] = $linkState;
            }
        }
        
        return $links;
    }

    
    /**
     * 
     * @return array
     */
    private function getLinksByLinkState($type, $state) {
        $linkStates = $this->getAll();
        $links = array();
        
        foreach ($linkStates as $linkState) {
            /* @var $linkState LinkState */
            
            if ($linkState->getType() == $type && $linkState->getState() == $state) {
                $links[] = $linkState;
            }
        }
        
        return $links;          
    }
    
    
    private function checkLinkStates() {
        if (is_null($this->webPage)) {
            $this->linkStates = array();
            return;
        }
        
        $linkFinder = new \webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder();
        
        $linkFinder->setSourceUrl($this->webPage->getUrl());
        $linkFinder->setSourceContent($this->webPage->getContent());
        
        if (!$linkFinder->hasUrls()) {
            $this->linkStates = array();
            return;            
        }
        
        foreach ($linkFinder->getAll() as $link) { 
            if ($this->isUrlToBeIncluded($link['url'])) {                
                $linkState = $this->getLinkState($link['url']);
                $linkState->setContext($link['element']);                
                $this->linkStates[] = $linkState;
            }
        }
    }
    
    
    /**
     * 
     * @param string $url
     * @return \webignition\HtmlDocumentLinkChecker\LinkState
     */
    private function getLinkState($url) {
        try {
            foreach ($this->httpMethodList as $methodIndex => $method) {
                $isLastMethod = $methodIndex == count($this->httpMethodList) - 1;            
                $response = $this->getResponseForHttpMethod($url, $method);

                if (!$response->isError() || $isLastMethod) {
                    return new LinkState(LinkState::TYPE_HTTP, $response->getStatusCode(), $url, '');
                }
            }            
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {
            return new LinkState(LinkState::TYPE_CURL, $curlException->getErrorNo(), $url, '');
        }       
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
        return !in_array($urlObject->getScheme(), $this->schemesToExclude);
    }    
    
}