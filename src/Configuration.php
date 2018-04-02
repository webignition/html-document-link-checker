<?php

namespace webignition\HtmlDocument\LinkChecker;

class Configuration
{
    const KEY_SCHEMES_TO_EXCLUDE = 'schemes-to-exclude';
    const KEY_URLS_TO_EXCLUDE = 'urls-to-exclude';
    const KEY_DOMAINS_TO_EXCLUDE = 'domains-to-exclude';
    const KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON = 'ignore-fragment-in-url-comparison';

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
    private $schemesToExclude = [
        self::URL_SCHEME_MAILTO,
        self::URL_SCHEME_ABOUT,
        self::URL_SCHEME_JAVASCRIPT,
        self::URL_SCHEME_FTP,
        self::URL_SCHEME_TEL
    ];

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
     * @param array $configurationValues
     */
    public function __construct(array $configurationValues = [])
    {
        if (array_key_exists(self::KEY_SCHEMES_TO_EXCLUDE, $configurationValues)) {
            $this->schemesToExclude = $configurationValues[self::KEY_SCHEMES_TO_EXCLUDE];
        }

        if (array_key_exists(self::KEY_URLS_TO_EXCLUDE, $configurationValues)) {
            $this->urlsToExclude = $configurationValues[self::KEY_URLS_TO_EXCLUDE];
        }

        if (array_key_exists(self::KEY_DOMAINS_TO_EXCLUDE, $configurationValues)) {
            $this->domainsToExclude = $configurationValues[self::KEY_DOMAINS_TO_EXCLUDE];
        }

        if (array_key_exists(self::KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON, $configurationValues)) {
            $this->ignoreFragmentInUrlComparison = $configurationValues[self::KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON];
        }
    }

    /**
     * @return string[]
     */
    public function getUrlsToExclude()
    {
        return $this->urlsToExclude;
    }

    /**
     * @return string[]
     */
    public function getDomainsToExclude()
    {
        return $this->domainsToExclude;
    }

    /**
     * @return string[]
     */
    public function getSchemesToExclude()
    {
        return $this->schemesToExclude;
    }

    /**
     * @return bool
     */
    public function getIgnoreFragmentInUrlComparison()
    {
        return $this->ignoreFragmentInUrlComparison;
    }
}
