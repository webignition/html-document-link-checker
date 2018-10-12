<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use webignition\HtmlDocument\LinkChecker\Configuration;
use webignition\HtmlDocument\LinkChecker\LinkChecker;
use webignition\UrlHealthChecker\Configuration as UrlHealthCheckerConfiguration;
use webignition\UrlHealthChecker\LinkState;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class LinkCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var HttpHistoryContainer
     */
    private $httpHistoryContainer;

    /**
     * @var HttpClient
     */
    private $httpClient;

    protected function setUp()
    {
        parent::setUp();

        $this->mockHandler = new MockHandler();
        $this->httpHistoryContainer = new HttpHistoryContainer();

        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push(Middleware::history($this->httpHistoryContainer));

        $this->httpClient = new HttpClient(['handler' => $handlerStack]);
    }

    private function appendHttpFixtures(array $httpFixtures)
    {
        foreach ($httpFixtures as $httpFixture) {
            $this->mockHandler->append($httpFixture);
        }
    }

    private function createLinkChecker(?Configuration $configuration = null): LinkChecker
    {
        if (empty($configuration)) {
            $configuration = new Configuration();
        }

        return new LinkChecker($configuration, $this->httpClient);
    }

    /**
     * @dataProvider getLinkStateDataProvider
     *
     * @param array $httpFixtures
     * @param Configuration $configuration
     * @param LinkState $expectedLinkState
     */
    public function testGetLinkState(
        array $httpFixtures,
        Configuration $configuration,
        LinkState $expectedLinkState
    ) {
        $url = 'http://example.com/';

        $this->appendHttpFixtures($httpFixtures);
        $linkChecker = $this->createLinkChecker($configuration);

        $this->assertEquals($expectedLinkState, $linkChecker->getLinkState($url));
    }

    public function getLinkStateDataProvider(): array
    {
        return [
            'excessive redirect counts as error' => [
                'httpFixtures' => array_fill(0, 6, new Response(301, ['location' => '/redirect1'])),
                'configuration' => new Configuration([
                    Configuration::KEY_URL_HEALTH_CHECKER_CONFIGURATION => new UrlHealthCheckerConfiguration([
                        UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                        UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                    ]),
                ]),
                'expectedLinkState' => new LinkState(LinkState::TYPE_HTTP, 301),
            ],
            'retry on bad response' => [
                'httpFixtures' => [
                    new Response(500),
                    new Response(200),
                ],
                'configuration' => new Configuration([
                    Configuration::KEY_URL_HEALTH_CHECKER_CONFIGURATION => new UrlHealthCheckerConfiguration([
                        UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => true,
                        UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                    ]),
                ]),
                'expectedLinkState' => new LinkState(LinkState::TYPE_HTTP, 200),
            ],
            'curl timeout' => [
                'httpFixtures' => [
                    new ConnectException(
                        'cURL error 28: Operation timeout.',
                        new Request('GET', 'http://example.com/')
                    ),
                ],
                'configuration' => new Configuration([
                    Configuration::KEY_URL_HEALTH_CHECKER_CONFIGURATION => new UrlHealthCheckerConfiguration([
                        UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                        UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                    ]),
                ]),
                'expectedLinkState' => new LinkState(LinkState::TYPE_CURL, 28),
            ],
            'http internal server error' => [
                'httpFixtures' => array_fill(0, 6, new Response(500)),
                'configuration' => new Configuration([
                    Configuration::KEY_URL_HEALTH_CHECKER_CONFIGURATION => new UrlHealthCheckerConfiguration([
                        UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                        UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                    ]),
                ]),
                'expectedLinkState' => new LinkState(LinkState::TYPE_HTTP, 500),
            ],
            'http ok' => [
                'httpFixtures' => [
                    new Response(),
                ],
                'configuration' => new Configuration([
                    Configuration::KEY_URL_HEALTH_CHECKER_CONFIGURATION => new UrlHealthCheckerConfiguration([
                        UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                        UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                    ]),
                ]),
                'expectedLinkState' => new LinkState(LinkState::TYPE_HTTP, 200),
            ],
        ];
    }

    public function testNonErrorUrlsAreCheckedOnlyOnce()
    {
        $this->appendHttpFixtures([
            new Response(),
        ]);

        $url = 'http://example.com/';

        $linkChecker = $this->createLinkChecker();

        $iterationCount = 10;

        for ($i = 0; $i < $iterationCount; $i++) {
            $linkChecker->getLinkState($url);
        }

        $this->assertEquals(1, $this->httpHistoryContainer->count());
    }

    /**
     * @dataProvider excludedDomainsDataProvider
     *
     * @param string $url
     * @param bool $expectedIsExcluded
     */
    public function testExcludedDomains(string $url, bool $expectedIsExcluded)
    {
        $this->appendHttpFixtures([
            new Response(),
        ]);

        $configuration = new Configuration([
            Configuration::KEY_DOMAINS_TO_EXCLUDE => [
                'foo.example.com',
                'bar.example.com',
            ],
        ]);

        $linkChecker = $this->createLinkChecker($configuration);
        $this->assertEquals($expectedIsExcluded, empty($linkChecker->getLinkState($url)));
    }

    public function excludedDomainsDataProvider(): array
    {
        return [
            'http://foo.example.com/ is excluded' => [
                'url' => 'http://foo.example.com/',
                'expectedIsExcluded' => true,
            ],
            'http://foo.example.com/path is excluded' => [
                'url' => 'http://foo.example.com/path',
                'expectedIsExcluded' => true,
            ],
            'https://foo.example.com/ is excluded' => [
                'url' => 'http://foo.example.com/',
                'expectedIsExcluded' => true,
            ],
            'http://bar.example.com/ is excluded' => [
                'url' => 'http://bar.example.com/',
                'expectedIsExcluded' => true,
            ],
            'http://example.com/ is not excluded' => [
                'url' => 'http://example.com/',
                'expectedIsExcluded' => false,
            ],
        ];
    }

    /**
     * @dataProvider excludedSchemesDataProvider
     *
     * @param string $url
     * @param bool $expectedIsExcluded
     */
    public function testExcludedSchemes(string $url, bool $expectedIsExcluded)
    {
        $this->appendHttpFixtures([
            new Response(),
        ]);

        $linkChecker = $this->createLinkChecker();
        $this->assertEquals($expectedIsExcluded, empty($linkChecker->getLinkState($url)));
    }

    public function excludedSchemesDataProvider(): array
    {
        return [
            'ftp scheme is excluded by default' => [
                'url' => 'ftp://example.com/',
                'expectedIsExcluded' => true,
            ],
            'mailto scheme is excluded by default' => [
                'url' => 'mailto:user@example.com',
                'expectedIsExcluded' => true,
            ],
            'tel scheme is excluded by default' => [
                'url' => 'tel:0123456789',
                'expectedIsExcluded' => true,
            ],
            'about:blank scheme is excluded by default' => [
                'url' => 'about:blank',
                'expectedIsExcluded' => true,
            ],
            'javascript scheme is excluded by default' => [
                'url' => 'javascript:void(0)',
                'expectedIsExcluded' => true,
            ],
            'http scheme is excluded by default' => [
                'url' => 'http://example.com/',
                'expectedIsExcluded' => false,
            ],
            'https scheme is excluded by default' => [
                'url' => 'https://example.com/',
                'expectedIsExcluded' => false,
            ],
        ];
    }

    /**
     * @dataProvider excludedUrlsDataProvider
     *
     * @param string $url
     * @param bool $expectedIsExcluded
     */
    public function testExcludedUrls(string $url, bool $expectedIsExcluded)
    {
        $this->appendHttpFixtures([
            new Response(),
        ]);

        $configuration = new Configuration([
            Configuration::KEY_URLS_TO_EXCLUDE => [
                'http://example.com/one',
                'http://example.com/two',
            ],
        ]);

        $linkChecker = $this->createLinkChecker($configuration);
        $this->assertEquals($expectedIsExcluded, empty($linkChecker->getLinkState($url)));
    }

    public function excludedUrlsDataProvider(): array
    {
        return [
            'http://example.com/one is excluded' => [
                'url' => 'http://example.com/one',
                'expectedIsExcluded' => true,
            ],
            'http://example.com/two is excluded' => [
                'url' => 'http://example.com/two',
                'expectedIsExcluded' => true,
            ],
            'https://example.com/one is not excluded' => [
                'url' => 'https://example.com/one',
                'expectedIsExcluded' => false,
            ],
            'http://example.com/ is not excluded' => [
                'url' => 'http://example.com/',
                'expectedIsExcluded' => false,
            ],
        ];
    }

    public function testIgnoreUrlFragmentInComparisons()
    {
        $this->appendHttpFixtures([
            new Response(),
        ]);

        $url1 = 'http://example.com/#one';
        $url2 = 'http://example.com/#two';
        $url3 = 'http://example.com/';

        $configuration = new Configuration([
            Configuration::KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON => true,
        ]);

        $linkChecker = $this->createLinkChecker($configuration);

        $linkChecker->getLinkState($url1);
        $linkChecker->getLinkState($url2);
        $linkChecker->getLinkState($url3);

        $this->assertEquals(1, $this->httpHistoryContainer->count());
    }
}
