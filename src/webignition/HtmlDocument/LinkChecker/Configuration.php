<?php
namespace webignition\HtmlDocument\LinkChecker;

class Configuration {           
    
    const URL_SCHEME_MAILTO = 'mailto';
    const URL_SCHEME_ABOUT = 'about';
    const URL_SCEME_JAVASCRIPT = 'javascript';
    const HTTP_STATUS_CODE_OK = 200;
    const HTTP_STATUS_CODE_METHOD_NOT_ALLOWED = 405;
    const HTTP_STATUS_CODE_NOT_IMPLEMENTED = 501;
    
    const HTTP_METHOD_HEAD = 'HEAD';
    const HTTP_METHOD_GET = 'GET';
    
    const CURL_MALFORMED_URL_CODE = 3;
    const CURL_MALFORMED_URL_MESSAGE = 'The URL was not properly formatted.';   
    
    const BAD_REQUEST_LIMIT = 3;
    
    const DEFAULT_REQUEST_TIMEOUT = 10;    
    const DEFAULT_REQUEST_CONNECT_TIMEOUT = 10;    
    
    /**
     *
     * @var array
     */
    private $userAgents = array();
    
    
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
        self::URL_SCHEME_ABOUT,
        self::URL_SCEME_JAVASCRIPT
    );
    
    
    /**
     *
     * @var \Guzzle\Http\Message\Request
     */
    private $baseRequest = null;
    
    
    /**
     *
     * @var array
     */
    private $urlsToExclude = array();
    
    
    /**
     *
     * @var array
     */
    private $domainsToExclude = array();
    
    
    /**
     *
     * @var boolean
     */
    private $retryOnBadResponse = true;
    
    
    /**
     *
     * @var boolean
     */
    private $toggleUrlEncoding = false;
    
    
    /**
     *
     * @var array
     */
    private $cookies = array();


    /**
     * @var bool
     */
    private $ignoreFragmentInUrlComparison = false;
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Request $request
     * @return \webignition\HtmlDocument\LinkChecker\Configuration
     */
    public function setBaseRequest(\Guzzle\Http\Message\Request $request) {
        $this->baseRequest = $request;
        return $this;
    }
    
    
    
    /**
     * 
     * @return \Guzzle\Http\Message\Request $request
     */
    public function getBaseRequest() {
        if (is_null($this->baseRequest)) {
            $client = new \Guzzle\Http\Client;            
            $client->addSubscriber(new \Guzzle\Plugin\History\HistoryPlugin());            
            $this->baseRequest = $client->get();
        }
        
        return $this->baseRequest;
    }     
    
    
    /**
     * 
     * @return \webignition\HtmlDocument\LinkChecker\Configuration
     */
    public function enableToggleUrlEncoding() {
        $this->toggleUrlEncoding = true;
        return $this;
    }
    
    
    /**
     * 
     * @return \webignition\HtmlDocument\LinkChecker\Configuration
     */
    public function disableToggleUrlEncoding() {
        $this->toggleUrlEncoding = false;
        return $this;
    }    
    
    
    /**
     * 
     * @return boolean
     */
    public function getToggleUrlEncoding() {
        return $this->toggleUrlEncoding;
    }
    
    
    /**
     * 
     * @return \webignition\HtmlDocument\LinkChecker\Configuration
     */
    public function enableRetryOnBadResponse() {
        $this->retryOnBadResponse = true;
        return $this;
    }
    
    
    /**
     * 
     * @return \webignition\HtmlDocument\LinkChecker\Configuration
     */
    public function disableRetryOnBadResponse() {
        $this->retryOnBadResponse = false;
        return $this;
    }    
    
    
    /**
     * 
     * @return boolean
     */
    public function getRetryOnBadResponse() {
        return $this->retryOnBadResponse;
    }
    
    
    /**
     * 
     * @param string[] $httpMethodList
     * @return \webignition\HtmlDocument\LinkChecker\Configuration
     */
    public function setHttpMethodList($httpMethodList) {
        $this->httpMethodList = $httpMethodList;
        return $this;
    }
    
    
    /**
     * 
     * @return string[]
     */
    public function getHttpMethodList() {
        return $this->httpMethodList;
    }
    
    
    /**
     * 
     * @param string[] $userAgents
     * @return \webignition\HtmlDocument\LinkChecker\Configuration
     */
    public function setUserAgents($userAgents) {
        $this->userAgents = $userAgents;
        return $this;
    }
    
    
    /**
     * 
     * @return string[]
     */
    public function getUserAgents() {
        return $this->userAgents;
    }
    
    
    /**
     * 
     * @param string[] $urlsToExclude
     * @return \webignition\HtmlDocument\LinkChecker\Configuration
     */
    public function setUrlsToExclude($urlsToExclude) {
        $this->urlsToExclude = $urlsToExclude;
        return $this;
    }
    
    
    /**
     * 
     * @return string[]
     */
    public function getUrlsToExclude() {
        return $this->urlsToExclude;
    }
    
    
    /**
     * 
     * @param string[] $domainsToExclude
     * @return \webignition\HtmlDocument\LinkChecker\Configuration
     */
    public function setDomainsToExclude($domainsToExclude) {
        $this->domainsToExclude = $domainsToExclude;
        return $this;
    }
    
    
    /**
     * 
     * @return string[]
     */
    public function getDomainsToExclude() {
        return $this->domainsToExclude;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getUserAgentSelectionForRequest() {
        if (count($this->userAgents)) {
            return $this->userAgents;
        }
        
        return $this->getBaseRequest()->getClient()->get()->getHeader('User-Agent')->toArray();     
    }
    
    
    
    /**
     * 
     * @param string[] $schemes
     * @return \webignition\HtmlDocument\LinkChecker\Configuration
     */
    public function setSchemesToExclude($schemes) {
        $this->schemesToExclude = $schemes;
        return $this;
    }
    
    
    /**
     * 
     * @return string[]
     */
    public function getSchemesToExclude() {
        return $this->schemesToExclude;
    }
    
    /**
     * 
     * @param array $cookies
     * @return Configuration
     */
    public function setCookies($cookies) {
        $this->cookies = $cookies;
        return $this;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getCookies() {
        return $this->cookies;
    }


    /**
     * @return Configuration
     */
    public function enableIgnoreFragmentInUrlComparison() {
        $this->ignoreFragmentInUrlComparison = true;
        return $this;
    }


    /**
     * @return Configuration
     */
    public function disableIgnoreFragmentInUrlComparison() {
        $this->ignoreFragmentInUrlComparison = false;
        return $this;
    }


    /**
     * @return bool
     */
    public function ignoreFragmentInUrlComparison() {
        return $this->ignoreFragmentInUrlComparison;
    }
    
}