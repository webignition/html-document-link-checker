<?php
namespace webignition\HtmlDocumentLinkChecker;

class LinkState {
    
    const TYPE_HTTP = 'http';
    const TYPE_CURL = 'curl';
    
    
    /**
     *
     * @var string
     */
    private $type = null;
    
    
    /**
     *
     * @var int
     */
    private $state = null;
    
    
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
     * @param string $type
     * @param int $state
     */
    public function __construct($type, $state, $url, $context) {
        $this->setType($type);
        $this->setState($state);
        $this->setUrl($url);
        $this->setContext($context);
    }
    
    
    /**
     * 
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getType() {
        return $this->type;
    }
    
    
    /**
     * 
     * @param int $state
     */
    public function setState($state) {
        $this->state = $state;
    }
    
    
    /**
     * 
     * @return int
     */
    public function getState() {
        return $this->state;
    }
    
    
    /**
     * 
     * @param \webignition\HtmlDocumentLinkChecker\LinkState $linkState
     * @return boolean
     */
    public function equals(LinkState $linkState) {
        if ($this->getType() != $linkState->getType()) {
            return false;
        }        
        
        if ($this->getState() != $linkState->getState()) {
            return false;
        }        
        
        if ($this->getContext() != $linkState->getContext()) {
            return false;
        }        
        
        if ($this->getUrl() != $linkState->getUrl()) {
            return false;
        }
        
        return true; 
   }
    
    
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