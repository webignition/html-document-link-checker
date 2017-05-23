<?php
namespace webignition\HtmlDocument\LinkChecker;

use GuzzleHttp\Client as HttpClient;

class Configuration
{
    const URL_SCHEME_MAILTO = 'mailto';
    const URL_SCHEME_ABOUT = 'about';
    const URL_SCHEME_JAVASCRIPT = 'javascript';
    const URL_SCHEME_FTP = 'ftp';
    const URL_SCHEME_TEL = 'tel';

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
     * @var string[]
     */
    private $schemesToExclude = array(
        self::URL_SCHEME_MAILTO,
        self::URL_SCHEME_ABOUT,
        self::URL_SCHEME_JAVASCRIPT,
        self::URL_SCHEME_FTP,
        self::URL_SCHEME_TEL
    );

    /**
     * @var array
     */
    private $urlsToExclude = [];

    /**
     * @var array
     */
    private $domainsToExclude = [];

    /**
     * @var bool
     */
    private $ignoreFragmentInUrlComparison = false;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @param string[] $urlsToExclude
     *
     * @return self
     */
    public function setUrlsToExclude($urlsToExclude)
    {
        $this->urlsToExclude = $urlsToExclude;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getUrlsToExclude()
    {
        return $this->urlsToExclude;
    }

    /**
     * @param string[] $domainsToExclude
     *
     * @return self
     */
    public function setDomainsToExclude($domainsToExclude)
    {
        $this->domainsToExclude = $domainsToExclude;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getDomainsToExclude()
    {
        return $this->domainsToExclude;
    }

    /**
     * @param string[] $schemes
     *
     * @return self
     */
    public function setSchemesToExclude($schemes)
    {
        $this->schemesToExclude = $schemes;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSchemesToExclude()
    {
        return $this->schemesToExclude;
    }

    /**
     * @return self
     */
    public function enableIgnoreFragmentInUrlComparison()
    {
        $this->ignoreFragmentInUrlComparison = true;

        return $this;
    }

    /**
     * @return self
     */
    public function disableIgnoreFragmentInUrlComparison()
    {
        $this->ignoreFragmentInUrlComparison = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function ignoreFragmentInUrlComparison()
    {
        return $this->ignoreFragmentInUrlComparison;
    }


    /**
     * @param HttpClient $httpClient
     *
     * @return self
     */
    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = new HttpClient();
        }

        return $this->httpClient;
    }
}
