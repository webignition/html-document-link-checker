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
        $linkStates = $this->getLinkStates();
        $links = array();
        
        $comparator = new LinkState(LinkState::TYPE_HTTP, $statusCode);
        
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
                $response = $request->send();
            } catch (\Guzzle\Http\Exception\BadResponseException $badResponseException) {
                $response = $badResponseException->getResponse();
            }
            
            $this->linkStates[$url] = new LinkState('http', $response->getStatusCode());
        }
    }
    
}