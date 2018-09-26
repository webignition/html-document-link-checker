<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use QueryPath\Exception as QueryPathException;
use webignition\HtmlDocument\LinkChecker\Configuration;
use webignition\HtmlDocument\LinkChecker\LinkChecker;
use webignition\HtmlDocument\LinkChecker\LinkResult;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\Tests\HtmlDocument\LinkChecker\Factory\FixtureLoader;
use webignition\UrlHealthChecker\Configuration as UrlHealthCheckerConfiguration;
use webignition\UrlHealthChecker\LinkState;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\WebPage\WebPage;

class LinkCheckerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAllNoWebPageSet()
    {
        $linkChecker = new LinkChecker(new Configuration(), new HttpClient());

        $this->assertEquals([], $linkChecker->getAll());
    }

    /**
     * @dataProvider getAllGetErroredGetWorkingDataProvider
     *
     * @param WebPage $webPage
     * @param array $httpFixtures
     * @param Configuration $configuration
     * @param LinkResult[] $expectedGetAllResults
     * @param LinkResult[] $expectedGetErroredResults
     * @param LinkResult[] $expectedGetWorkingResults
     *
     * @throws GuzzleException
     * @throws QueryPathException
     */
    public function testGetAllGetErroredGetWorking(
        WebPage $webPage,
        $httpFixtures,
        Configuration $configuration,
        $expectedGetAllResults,
        $expectedGetErroredResults,
        $expectedGetWorkingResults
    ) {
        $mockHandler = new MockHandler($httpFixtures);

        $httpClient = new HttpClient(['handler' => HandlerStack::create($mockHandler)]);

        $linkChecker = new LinkChecker($configuration, $httpClient);
        $linkChecker->setWebPage($webPage);

        $this->assertEquals($expectedGetAllResults, $linkChecker->getAll());
        $this->assertEquals($expectedGetErroredResults, $linkChecker->getErrored());
        $this->assertEquals($expectedGetWorkingResults, $linkChecker->getWorking());
    }

    /**
     * @return array
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeException
     */
    public function getAllGetErroredGetWorkingDataProvider()
    {
        $successResponse = new Response();
        $redirectResponse = new Response(301, ['location' => '/redirect1']);
        $internalServerErrorResponse = new Response(500);

        return [
            'no web page' => [
                'webPage' => $this->createWebPage('', 'http://example.com/'),
                'httpFixtures' => [],
                'configuration' => new Configuration(),
                'expectedGetAllResults' => [],
                'expectedGetErroredResults' => [],
                'expectedGetWorkingResults' => [],
            ],
            'no links' => [
                'webPage' => $this->createWebPage(FixtureLoader::load('no-links.html'), 'http://example.com/'),
                'httpFixtures' => [],
                'configuration' => new Configuration(),
                'expectedGetAllResults' => [],
                'expectedGetErroredResults' => [],
                'expectedGetWorkingResults' => [],
            ],
            'check urls only once' => [
                'webPage' => $this->createWebPage(FixtureLoader::load('repeated-urls.html'), 'http://example.com/'),
                'httpFixtures' => [
                    $successResponse,
                    $successResponse,
                    $successResponse,
                ],
                'configuration' => new Configuration([
                    Configuration::KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON => true,
                ]),
                'expectedGetAllResults' => [
                    $this->createLinkResult(
                        'http://example.com/',
                        '<a href="http://example.com/">Example One</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                    $this->createLinkResult(
                        'http://example.com/',
                        '<a href="http://example.com/">Example Two</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                    $this->createLinkResult(
                        'http://example.com/foo',
                        '<a href="http://example.com/foo">Example Foo One</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                    $this->createLinkResult(
                        'http://example.com/foo',
                        '<a href="http://example.com/foo">Example Foo Two</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                ],
                'expectedGetErroredResults' => [],
                'expectedGetWorkingResults' => [
                    $this->createLinkResult(
                        'http://example.com/',
                        '<a href="http://example.com/">Example One</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                    $this->createLinkResult(
                        'http://example.com/',
                        '<a href="http://example.com/">Example Two</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                    $this->createLinkResult(
                        'http://example.com/foo',
                        '<a href="http://example.com/foo">Example Foo One</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                    $this->createLinkResult(
                        'http://example.com/foo',
                        '<a href="http://example.com/foo">Example Foo Two</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                ],
            ],
            'excessive redirect counts as error' => [
                'webPage' => $this->createWebPage(FixtureLoader::load('single-link.html'), 'http://example.com/'),
                'httpFixtures' => [
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                ],
                'configuration' => new Configuration([
                    Configuration::KEY_URL_HEALTH_CHECKER_CONFIGURATION => new UrlHealthCheckerConfiguration([
                        UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                        UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                    ]),
                ]),
                'expectedGetAllResults' => [
                    $this->createLinkResult(
                        'http://example.com/',
                        '<a href="http://example.com/">Example no subdomain</a>',
                        LinkState::TYPE_HTTP,
                        301
                    ),
                ],
                'expectedGetErroredResults' => [
                    $this->createLinkResult(
                        'http://example.com/',
                        '<a href="http://example.com/">Example no subdomain</a>',
                        LinkState::TYPE_HTTP,
                        301
                    ),
                ],
                'expectedGetWorkingResults' => [],
            ],
            'do not reuse failed link state' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('two-links-same-url.html'),
                    'http://example.com/'
                ),
                'httpFixtures' => [
                    $internalServerErrorResponse,
                    $successResponse,
                ],
                'configuration' => new Configuration([
                    Configuration::KEY_URL_HEALTH_CHECKER_CONFIGURATION => new UrlHealthCheckerConfiguration([
                        UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                        UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                    ]),
                ]),
                'expectedGetAllResults' => [
                    $this->createLinkResult(
                        'http://example.com/',
                        '<a href="http://example.com/">Example 1</a>',
                        LinkState::TYPE_HTTP,
                        500
                    ),
                    $this->createLinkResult(
                        'http://example.com/',
                        '<a href="http://example.com/">Example 2</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                ],
                'expectedGetErroredResults' => [
                    $this->createLinkResult(
                        'http://example.com/',
                        '<a href="http://example.com/">Example 1</a>',
                        LinkState::TYPE_HTTP,
                        500
                    ),
                ],
                'expectedGetWorkingResults' => [
                    $this->createLinkResult(
                        'http://example.com/',
                        '<a href="http://example.com/">Example 2</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                ],
            ],
            'exclude domains' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('multiple-links.html'),
                    'http://example.com/'
                ),
                'httpFixtures' => [
                    $successResponse,
                ],
                'configuration' => new Configuration([
                    Configuration::KEY_DOMAINS_TO_EXCLUDE => [
                        'www.youtube.com',
                        'blog.example.com',
                        'example.com',
                    ],
                ]),
                'expectedGetAllResults' => [
                    $this->createLinkResult(
                        'http://twitter.com/example',
                        '<a href="http://twitter.com/example"><img src="/images/twitter.png"></a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                ],
                'expectedGetErroredResults' => [],
                'expectedGetWorkingResults' => [
                    $this->createLinkResult(
                        'http://twitter.com/example',
                        '<a href="http://twitter.com/example"><img src="/images/twitter.png"></a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                ],
            ],
            'exclude ftp mailto tel about:blank javascript schemes' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('excluded-url-schemes.html'),
                    'http://example.com/'
                ),
                'httpFixtures' => [],
                'configuration' => new Configuration(),
                'expectedGetAllResults' => [],
                'expectedGetErroredResults' => [],
                'expectedGetWorkingResults' => [],
            ],
            'exclude urls' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('multiple-links.html'),
                    'http://example.com/'
                ),
                'httpFixtures' => [
                    $successResponse,
                ],
                'configuration' => new Configuration([
                    Configuration::KEY_URLS_TO_EXCLUDE => [
                        'http://www.youtube.com/example',
                        'http://blog.example.com/',
                        'http://twitter.com/example',
                        'http://example.com/images/youtube.png',
                        'http://example.com/images/blog.png',
                    ],
                ]),
                'expectedGetAllResults' => [
                    $this->createLinkResult(
                        'http://example.com/images/twitter.png',
                        '<img src="/images/twitter.png">',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                ],
                'expectedGetErroredResults' => [],
                'expectedGetWorkingResults' => [
                    $this->createLinkResult(
                        'http://example.com/images/twitter.png',
                        '<img src="/images/twitter.png">',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                ],
            ],
            'ignore url fragment' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('two-links-different-fragments.html'),
                    'http://example.com/'
                ),
                'httpFixtures' => [
                    $successResponse,
                ],
                'configuration' => new Configuration([
                    Configuration::KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON => true,
                ]),
                'expectedGetAllResults' => [
                    $this->createLinkResult(
                        'http://example.com/#one',
                        '<a href="http://example.com/#one">Example One</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                    $this->createLinkResult(
                        'http://example.com/#two',
                        '<a href="http://example.com/#two">Example Two</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                ],
                'expectedGetErroredResults' => [],
                'expectedGetWorkingResults' => [
                    $this->createLinkResult(
                        'http://example.com/#one',
                        '<a href="http://example.com/#one">Example One</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                    $this->createLinkResult(
                        'http://example.com/#two',
                        '<a href="http://example.com/#two">Example Two</a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                ],
            ],
            'varied errors' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('multiple-links.html'),
                    'http://example.com/'
                ),
                'httpFixtures' => [
                    new ConnectException(
                        'cURL error 6: Couldn\'t resolve host. The given remote host was not resolved.',
                        new Request('GET', 'http://example.com/')
                    ),
                    new Response(404),
                    $internalServerErrorResponse,
                    new ConnectException(
                        'cURL error 28: Operation timeout.',
                        new Request('GET', 'http://example.com/')
                    ),
                    $successResponse,
                    new Response(999),
                ],
                'configuration' => new Configuration([
                    Configuration::KEY_URL_HEALTH_CHECKER_CONFIGURATION => new UrlHealthCheckerConfiguration([
                        UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                        UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                    ]),
                ]),
                'expectedGetAllResults' => [
                    $this->createLinkResult(
                        'http://www.youtube.com/example',
                        '<a href="http://www.youtube.com/example"><img src="/images/youtube.png"></a>',
                        LinkState::TYPE_CURL,
                        6
                    ),
                    $this->createLinkResult(
                        'http://example.com/images/youtube.png',
                        '<img src="/images/youtube.png">',
                        LinkState::TYPE_HTTP,
                        404
                    ),
                    $this->createLinkResult(
                        'http://blog.example.com/',
                        '<a href="http://blog.example.com"><img src="/images/blog.png"></a>',
                        LinkState::TYPE_HTTP,
                        500
                    ),
                    $this->createLinkResult(
                        'http://example.com/images/blog.png',
                        '<img src="/images/blog.png">',
                        LinkState::TYPE_CURL,
                        28
                    ),
                    $this->createLinkResult(
                        'http://twitter.com/example',
                        '<a href="http://twitter.com/example"><img src="/images/twitter.png"></a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                    $this->createLinkResult(
                        'http://example.com/images/twitter.png',
                        '<img src="/images/twitter.png">',
                        LinkState::TYPE_HTTP,
                        999
                    ),
                ],
                'expectedGetErroredResults' => [
                    $this->createLinkResult(
                        'http://www.youtube.com/example',
                        '<a href="http://www.youtube.com/example"><img src="/images/youtube.png"></a>',
                        LinkState::TYPE_CURL,
                        6
                    ),
                    $this->createLinkResult(
                        'http://example.com/images/youtube.png',
                        '<img src="/images/youtube.png">',
                        LinkState::TYPE_HTTP,
                        404
                    ),
                    $this->createLinkResult(
                        'http://blog.example.com/',
                        '<a href="http://blog.example.com"><img src="/images/blog.png"></a>',
                        LinkState::TYPE_HTTP,
                        500
                    ),
                    $this->createLinkResult(
                        'http://example.com/images/blog.png',
                        '<img src="/images/blog.png">',
                        LinkState::TYPE_CURL,
                        28
                    ),
                    $this->createLinkResult(
                        'http://example.com/images/twitter.png',
                        '<img src="/images/twitter.png">',
                        LinkState::TYPE_HTTP,
                        999
                    ),
                ],
                'expectedGetWorkingResults' => [
                    $this->createLinkResult(
                        'http://twitter.com/example',
                        '<a href="http://twitter.com/example"><img src="/images/twitter.png"></a>',
                        LinkState::TYPE_HTTP,
                        200
                    ),
                ],
            ],
        ];
    }

    /**
     * @param string $url
     * @param string $context
     * @param string $linkStateType
     * @param string|int $linkStateState
     *
     * @return LinkResult
     */
    private function createLinkResult($url, $context, $linkStateType, $linkStateState)
    {
        return new LinkResult(
            $url,
            $context,
            new LinkState($linkStateType, $linkStateState)
        );
    }

    /**
     * @param string $content
     * @param string $url
     * @return WebPage
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeException
     */
    private function createWebPage($content, $url)
    {
        $response = new Response(200, ['content-type' => 'text/html'], $content);
        $uri = new Uri($url);

        return new WebPage($response, $uri);
    }
}
