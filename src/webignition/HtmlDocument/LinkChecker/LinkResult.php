<?php
namespace webignition\HtmlDocument\LinkChecker;

use webignition\UrlHealthChecker\LinkState;

class LinkResult {
    
    /**
     *
     * @var string
     */
    private $context =  null;
    
    
    /**
     *
     * @var string
     */
    private $url = null;
    
    
    /**
     *
     * @var LinkState
     */
    private $linkState = null;
    
    
    /**
     * 
     * @param string $type
     * @param int $state
     */
    public function __construct($url, $context, LinkState $linkState) {
        $this->setUrl($url);
        $this->setContext($context);
        $this->setLinkState($linkState);
    }
    
    
    /**
     * 
     * @param \webignition\HtmlDocument\LinkChecker\LinkState $linkState
     */
    public function setLinkState(LinkState $linkState) {
        $this->linkState = $linkState;
    }
    
    
    /**
     * 
     * @return LinkState
     */
    public function getLinkState() {
        return $this->linkState;
    }
    
    
//    /**
//     * 
//     * @param \webignition\HtmlDocument\LinkChecker\LinkState $linkState
//     * @return boolean
//     */
//    public function equals(LinkState $linkState) {
//        if ($this->getType() != $linkState->getType()) {
//            return false;
//        }        
//        
//        if ($this->getState() != $linkState->getState()) {
//            return false;
//        }        
//        
//        if ($this->getContext() != $linkState->getContext()) {
//            return false;
//        }        
//        
//        if ($this->getUrl() != $linkState->getUrl()) {
//            return false;
//        }
//        
//        return true; 
//   }
    
    
    /**
     * 
     * @param string $context
     */
    public function setContext($context) {
        $this->context = $context;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getContext() {
        return $this->context;
    }    
    
    
    /**
     * 
     * @param string $url
     */
    public function setUrl($url) {
        $this->url = $url;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }
    
}