<?php

namespace webignition\HtmlDocument\LinkChecker;

class Configuration
{
    const KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON = 'ignore-fragment-in-url-comparison';

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
     * @var bool
     */
    private $ignoreFragmentInUrlComparison = false;

    /**
     * @param array $configurationValues
     */
    public function __construct(array $configurationValues = [])
    {
        if (array_key_exists(self::KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON, $configurationValues)) {
            $this->ignoreFragmentInUrlComparison = $configurationValues[self::KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON];
        }
    }

    public function getIgnoreFragmentInUrlComparison(): bool
    {
        return $this->ignoreFragmentInUrlComparison;
    }
}
