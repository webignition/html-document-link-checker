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
    private $schemesToExclude = array(
        self::URL_SCHEME_MAILTO,
        self::URL_SCHEME_ABOUT,
        self::URL_SCEME_JAVASCRIPT
    );

    
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
     * @var bool
     */
    private $ignoreFragmentInUrlComparison = false;
    
    
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