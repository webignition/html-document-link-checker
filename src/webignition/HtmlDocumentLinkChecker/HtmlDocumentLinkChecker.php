<?php
namespace webignition\HtmlDocumentLinkChecker;

class HtmlDocumentLinkChecker {
    
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
    public function getLinkStates() {
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
    public function getLinksByHttpState($statusCode) {
        return $this->getLinksByLinkState(new LinkState(LinkState::TYPE_HTTP, $statusCode));       
    }
    
    /**
     * 
     * @param int $statusCode
     * @return array
     */    
    public function getLinksByCurlState($curlCode) {
        return $this->getLinksByLinkState(new LinkState(LinkState::TYPE_CURL, $curlCode));       
    }
    
    
    /**
     * 
     * @return array
     */
    public function getErroredLinks() {
        $curlStateLinks = $this->getLinksByType(LinkState::TYPE_CURL);
        $httpStateLinks = $this->getLinksByType(LinkState::TYPE_HTTP);

        $links = $curlStateLinks;
        foreach ($httpStateLinks as $url => $linkState) {
            if ($this->isHttpErrorStatusCode($linkState->getState())) {
                $links[$url] = $linkState;
            }
        }
        
        return $links;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getWorkingLinks() {
        $httpStateLinks = $this->getLinksByType(LinkState::TYPE_HTTP);
        
        $links = array();
        foreach ($httpStateLinks as $url => $linkState) {
            if (!$this->isHttpErrorStatusCode($linkState->getState())) {
                $links[$url] = $linkState;
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
        $linkStates = $this->getLinkStates();
        $links = array();
        
        foreach ($linkStates as $url => $linkState) {
            if ($linkState->getType() == $type) {
                $links[$url] = $linkState;
            }
        }
        
        return $links;
    }

    
    /**
     * 
     * @param \webignition\HtmlDocumentLinkChecker\LinkState $comparator
     * @return array
     */
    private function getLinksByLinkState(LinkState $comparator) {
        $linkStates = $this->getLinkStates();
        $links = array();
        
        foreach ($linkStates as $url => $linkState) {
            /* @var $linkState LinkState */
            
            if ($linkState->equals($comparator)) {
                $links[] = $url;
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
        
        foreach ($linkFinder->getUrls() as $url) {
            $request = $this->getHttpClient()->head($url);

            try {
                try {
                    $response = $request->send();
                } catch (\Guzzle\Http\Exception\BadResponseException $badResponseException) {
                    $response = $badResponseException->getResponse();
                } 
                
                if (!isset($this->linkStates[$url])) {
                    $this->linkStates[$url] = new LinkState(LinkState::TYPE_HTTP, $response->getStatusCode());                
                }
            } catch (\Guzzle\Http\Exception\CurlException $curlException) {
                if (!isset($this->linkStates[$url])) {
                    $this->linkStates[$url] = new LinkState(LinkState::TYPE_CURL, $curlException->getErrorNo());                    
                }                
            }
        }
    }
    
}