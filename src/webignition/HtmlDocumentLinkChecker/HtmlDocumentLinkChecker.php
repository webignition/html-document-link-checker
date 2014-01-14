<?php
namespace webignition\HtmlDocumentLinkChecker;

class HtmlDocumentLinkChecker {           
    
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
     * @var array
     */
    private $domainsToExclude = array();
    
    
    /**
     *
     * @var array
     */
    private $badRequestCount = 0;
    
    
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
    private $requestOptions = array(
        'timeout'         => self::DEFAULT_REQUEST_TIMEOUT,
        'connect_timeout' => self::DEFAULT_REQUEST_CONNECT_TIMEOUT        
    );
    
    
    public function enableToggleUrlEncoding() {
        $this->toggleUrlEncoding = true;
    }
    
    
    public function disableToggleUrlEncoding() {
        $this->toggleUrlEncoding = false;
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
     * @param array $options
     */
    public function setRequestOptions($options) {
        $this->requestOptions = $options;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getRequestOptions() {
        return $this->requestOptions;
    }
    
    
    /**
     * 
     * @param boolean $retryOnBadResponse
     */
    public function setRetryOnBadResponse($retryOnBadResponse) {
        $this->retryOnBadResponse = $retryOnBadResponse;
    }
    
    
    /**
     * 
     * @param array $httpMethodList
     */
    public function setHttpMethodList($httpMethodList) {
        $this->httpMethodList = $httpMethodList;
    }
    
    
    /**
     * 
     * @param array $userAgents
     */
    public function setUserAgents($userAgents) {
        $this->userAgents = $userAgents;
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
     * @param array $domainsToExclude
     */
    public function setDomainsToExclude($domainsToExclude) {
        $this->domainsToExclude = $domainsToExclude;
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
            if ($this->isErrored($linkCheckResult->getLinkState())) {
                $links[] = $linkCheckResult;
            }
        }
        
        return $links;
    }
    
    
    /**
     * 
     * @param \webignition\HtmlDocumentLinkChecker\LinkState $linkState
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
     * @return \webignition\HtmlDocumentLinkChecker\LinkState
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
     * @return \webignition\HtmlDocumentLinkChecker\LinkState
     */
    private function deriveLinkState($url) {
        $requests = $this->buildRequestSet($url);
        
        try {
            foreach ($requests as $requestIndex => $request) {               
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
        $useEncodingOptions = ($this->getToggleUrlEncoding())
            ? array(true, false)
            : array(true);
        
        $requests = array();
        
        $userAgentSelection = $this->getUserAgentSelectionForRequest();
        
        foreach ($userAgentSelection as $userAgent) {
            foreach ($this->httpMethodList as $methodIndex => $method) {
                foreach ($useEncodingOptions as $useEncoding) {
                    $requestUrl = \Guzzle\Http\Url::factory($url);
                    $requestUrl->getQuery()->useUrlEncoding($useEncoding);

                    $request = $this->getHttpClient()->createRequest($method, $url, array(
                        'Referer' => $this->webPage->getUrl()
                    ), null, $this->getRequestOptions());                    
                    
                    $request->setUrl($requestUrl);    
                    $request->setHeader('user-agent', $userAgent);
                    
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
     * @param string $url
     * @param string $method
     * @return \Guzzle\Http\Message\Response
     */
    private function getResponseForHttpMethod($url, $method) {                
        $requestUrl = \Guzzle\Http\Url::factory($url);
        $requestUrl->getQuery()->useUrlEncoding(false);
        
        $request = $this->getHttpClient()->createRequest($method, $url, array(
            'Referer' => $this->webPage->getUrl()
        ), null, $this->getRequestOptions());
        
        $request->setUrl($requestUrl);
        
        echo $request->send();
        exit();
        
        $userAgentSelection = $this->getUserAgentSelectionForRequest($request);        
        
        foreach ($userAgentSelection as $userAgentIndex => $userAgent) {
            $isLastUserAgent = $userAgentIndex == count($userAgentSelection) - 1;
            $request->setHeader('user-agent', $userAgent);
            
            $this->badRequestCount = 0;
            $response =  $this->getHttpResponse($request);
            
            if (!$this->isStrictHttpErrorStatusCode($response->getStatusCode())) {
                return $response;
            }
            
            if ($isLastUserAgent) {
                return $response;
            }           
        }    
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
        if ($this->retryOnBadResponse === false) {
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
     * @return array
     */
    private function getUserAgentSelectionForRequest() {
        if (count($this->userAgents)) {
            return $this->userAgents;
        }
        
        return $this->getHttpClient()->get()->getHeader('User-Agent')->toArray();     
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
        return !in_array($url->getScheme(), $this->schemesToExclude);
    }
    
    
    /**
     * 
     * @param \webignition\NormalisedUrl\NormalisedUrl $url
     * @return boolean
     */
    private function isUrlDomainToBeIncluded(\webignition\NormalisedUrl\NormalisedUrl $url) {
        return !in_array($url->getHost(), $this->domainsToExclude);
    }
    
}