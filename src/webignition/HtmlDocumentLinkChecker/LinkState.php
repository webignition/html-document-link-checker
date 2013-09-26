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
     * @param string $type
     * @param int $state
     */
    public function __construct($type, $state) {
        $this->setType($type);
        $this->setState($state);
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
        return $this->getType() == $linkState->getType() && $this->getState() == $linkState->getState();
    }
    
}