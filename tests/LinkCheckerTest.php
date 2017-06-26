<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use GuzzleHttp\Message\ResponseInterface;
use Mockery\MockInterface;
use PHPUnit_Framework_TestCase;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\ResponseInterface as HttpResponse;
use GuzzleHttp\Subscriber\History as HttpHistorySubscriber;
use GuzzleHttp\Subscriber\Mock as HttpMockSubscriber;
use webignition\HtmlDocument\LinkChecker\LinkChecker;
use webignition\HtmlDocument\LinkChecker\LinkResult;
use webignition\UrlHealthChecker\Configuration as UrlHeatlCheckerConfiguration;
use webignition\UrlHealthChecker\LinkState;
use webignition\WebResource\WebPage\WebPage;

class LinkCheckerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getAllGetErroredGetWorkingDataProvider
     *
     * @param string $responseUrl
     * @param string $responseBody
     * @param array $urlHealthCheckerResponses
     * @param LinkResult[] $expectedGetAllResults
     * @param LinkResult[] $expectedGetErroredResults
     * @param LinkResult[] $expectedGetWorkingResults
     * @param callable|null $linkCheckerModifierFunction
     */
    public function testGetAllGetErroredGetWorking(
        $responseUrl,
        $responseBody,
        $urlHealthCheckerResponses,
        $expectedGetAllResults,
        $expectedGetErroredResults,
        $expectedGetWorkingResults,
        $linkCheckerModifierFunction = null
    ) {
        $httpClient = $this->createHttpClient($urlHealthCheckerResponses);

        $webPage = new WebPage();
        $webPage->setHttpResponse($this->createHttpResponse($responseUrl, $responseBody));

        $linkChecker = new LinkChecker();
        $linkChecker->getConfiguration()->setHttpClient($httpClient);
        $linkChecker->setWebPage($webPage);

        if (is_callable($linkCheckerModifierFunction)) {
            call_user_func(function () use ($linkCheckerModifierFunction, $linkChecker) {
                call_user_func($linkCheckerModifierFunction, $linkChecker);
            }, $linkChecker);
        }

        $this->assertEquals($expectedGetAllResults, $linkChecker->getAll());
        $this->assertEquals($expectedGetErroredResults, $linkChecker->getErrored());
        $this->assertEquals($expectedGetWorkingResults, $linkChecker->getWorking());
    }

    /**
     * @return array
     */
    public function getAllGetErroredGetWorkingDataProvider()
    {
        return [
            'no web page' => [
                'responseUrl' => 'http://example.com',
                'responseBody' => '',
                'urlHealthCheckerResponses' => [],
                'expectedGetAllResults' => [],
                'expectedGetErroredResults' => [],
                'expectedGetWorkingResults' => [],
            ],
            'no links' => [
                'responseUrl' => 'http://example.com',
                'responseBody' => $this->getHtmlDocumentFixture('no-links'),
                'urlHealthCheckerResponses' => [],
                'expectedGetAllResults' => [],
                'expectedGetErroredResults' => [],
                'expectedGetWorkingResults' => [],
            ],
            'check urls only once' => [
                'responseUrl' => 'http://example.com',
                'responseBody' => $this->getHtmlDocumentFixture('repeated-urls'),
                'urlHealthCheckerResponses' => [
                    'HTTP/1.1 200 OK',
                    'HTTP/1.1 200 OK',
                    'HTTP/1.1 200 OK',
                ],
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
                'responseUrl' => 'http://example.com/foo',
                'responseBody' => $this->getHtmlDocumentFixture('single-link'),
                'urlHealthCheckerResponses' => [
                    "HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect1\r\nContent-Length: 0\r\n\r\n",
                    "HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect1\r\nContent-Length: 0\r\n\r\n",
                    "HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect1\r\nContent-Length: 0\r\n\r\n",
                    "HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect1\r\nContent-Length: 0\r\n\r\n",
                    "HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect1\r\nContent-Length: 0\r\n\r\n",
                    "HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect1\r\nContent-Length: 0\r\n\r\n",
                ],
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
                'linkCheckerModifierFunction' => function (LinkChecker $linkChecker) {
                    $linkChecker->getUrlHealthChecker()->setConfiguration(new UrlHeatlCheckerConfiguration([
                        UrlHeatlCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                        UrlHeatlCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                        UrlHeatlCheckerConfiguration::CONFIG_KEY_HTTP_CLIENT =>
                            $linkChecker->getConfiguration()->getHttpClient(),
                    ]));
                },
            ],
            'do not reuse failed link state' => [
                'responseUrl' => 'http://example.com',
                'responseBody' => $this->getHtmlDocumentFixture('two-links-same-url'),
                'urlHealthCheckerResponses' => [
                    'HTTP/1.1 500 Internal Server Error',
                    'HTTP/1.1 200 OK'
                ],
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
                'linkCheckerModifierFunction' => function (LinkChecker $linkChecker) {
                    $linkChecker->getUrlHealthChecker()->setConfiguration(new UrlHeatlCheckerConfiguration([
                        UrlHeatlCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                        UrlHeatlCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                        UrlHeatlCheckerConfiguration::CONFIG_KEY_HTTP_CLIENT =>
                            $linkChecker->getConfiguration()->getHttpClient(),
                    ]));
                },
            ],
            'exclude domains' => [
                'responseUrl' => 'http://example.com',
                'responseBody' => $this->getHtmlDocumentFixture('multiple-links'),
                'urlHealthCheckerResponses' => [
                    'HTTP/1.1 200 OK'
                ],
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
                'linkCheckerModifierFunction' => function (LinkChecker $linkChecker) {
                    $linkChecker->getConfiguration()->setDomainsToExclude([
                        'www.youtube.com',
                        'blog.example.com',
                        'example.com',
                    ]);
                },
            ],
            'exclude ftp mailto tel about:blank javascript schemes' => [
                'responseUrl' => 'http://example.com',
                'responseBody' => $this->getHtmlDocumentFixture('excluded-url-schemes'),
                'urlHealthCheckerResponses' => [],
                'expectedGetAllResults' => [],
                'expectedGetErroredResults' => [],
                'expectedGetWorkingResults' => [],
            ],
            'exclude urls' => [
                'responseUrl' => 'http://example.com',
                'responseBody' => $this->getHtmlDocumentFixture('multiple-links'),
                'urlHealthCheckerResponses' => [
                    'HTTP/1.1 200 OK',
                ],
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
                'linkCheckerModifierFunction' => function (LinkChecker $linkChecker) {
                    $linkChecker->getConfiguration()->setUrlsToExclude([
                        'http://www.youtube.com/example',
                        'http://blog.example.com/',
                        'http://twitter.com/example',
                        'http://example.com/images/youtube.png',
                        'http://example.com/images/blog.png',
                    ]);
                },
            ],
            'ignore url fragment' => [
                'responseUrl' => 'http://example.com',
                'responseBody' => $this->getHtmlDocumentFixture('two-links-different-fragments'),
                'urlHealthCheckerResponses' => [
                    'HTTP/1.1 200 OK'
                ],
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
                'linkCheckerModifierFunction' => function (LinkChecker $linkChecker) {
                    $linkChecker->getConfiguration()->enableIgnoreFragmentInUrlComparison();
                },
            ],
            'varied errors' => [
                'responseUrl' => 'http://example.com',
                'responseBody' => $this->getHtmlDocumentFixture('multiple-links'),
                'urlHealthCheckerResponses' => [
                    new ConnectException(
                        'cURL error 6: Couldn\'t resolve host. The given remote host was not resolved.',
                        new Request('GET', 'http://example.com/')
                    ),
                    'HTTP/1.1 404',
                    'HTTP/1.1 500',
                    new ConnectException(
                        'cURL error 28: Operation timeout.',
                        new Request('GET', 'http://example.com/')
                    ),
                    'HTTP/1.1 200',
                    'HTTP/1.1 999',
                ],
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
                'linkCheckerModifierFunction' => function (LinkChecker $linkChecker) {
                    $linkChecker->getUrlHealthChecker()->setConfiguration(new UrlHeatlCheckerConfiguration([
                        UrlHeatlCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                        UrlHeatlCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                        UrlHeatlCheckerConfiguration::CONFIG_KEY_HTTP_CLIENT =>
                            $linkChecker->getConfiguration()->getHttpClient(),
                    ]));
                },
            ],
        ];
    }

    /**
     * @param string $url
     * @param string $body
     *
     * @return MockInterface|HttpResponse
     */
    private function createHttpResponse($url, $body)
    {
        $httpResponse = \Mockery::mock(ResponseInterface::class);
        $httpResponse
            ->shouldReceive('getHeader')
            ->with('content-type')
            ->andReturn('text/html');

        $httpResponse
            ->shouldReceive('getEffectiveUrl')
            ->andReturn($url);

        $httpResponse
            ->shouldReceive('getBody')
            ->andReturn($body);

        return $httpResponse;
    }

    /**
     * @param array $responseFixtures
     *
     * @return HttpClient
     */
    private function createHttpClient($responseFixtures)
    {
        $httpClient = new HttpClient();
        $httpClient->getEmitter()->attach(new HttpHistorySubscriber());

        $httpClient->getEmitter()->attach(
            new HttpMockSubscriber(
                $responseFixtures
            )
        );

        return $httpClient;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getHtmlDocumentFixture($name)
    {
        return file_get_contents(__DIR__ . '/fixtures/html-documents/' . $name . '.html');
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
}
