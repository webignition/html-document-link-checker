<?php
/** @noinspection PhpDocSignatureInspection */

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
use webignition\UrlHealthChecker\UrlHealthChecker;

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

    private function createUrlHealthChecker(?UrlHealthCheckerConfiguration $configuration = null): UrlHealthChecker
    {
        $configuration = $configuration ?? new UrlHealthCheckerConfiguration();

        $urlHealthChecker = new UrlHealthChecker();
        $urlHealthChecker->setConfiguration($configuration);
        $urlHealthChecker->setHttpClient($this->httpClient);

        return $urlHealthChecker;
    }

    /**
     * @dataProvider getLinkStateDataProvider
     */
    public function testGetLinkState(
        array $httpFixtures,
        Configuration $configuration,
        UrlHealthCheckerConfiguration $urlHealthCheckerConfiguration,
        LinkState $expectedLinkState
    ) {
        $this->appendHttpFixtures($httpFixtures);

        $urlHealthChecker = $this->createUrlHealthChecker($urlHealthCheckerConfiguration);

        $linkChecker = new LinkChecker($configuration, $urlHealthChecker);
        $url = 'http://example.com/';

        $this->assertEquals($expectedLinkState, $linkChecker->getLinkState($url));
    }

    public function getLinkStateDataProvider(): array
    {
        return [
            'excessive redirect counts as error' => [
                'httpFixtures' => array_fill(0, 6, new Response(301, ['location' => '/redirect1'])),
                'configuration' => new Configuration(),
                'urlHealthCheckerConfiguration' => new UrlHealthCheckerConfiguration([
                    UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                    UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                ]),
                'expectedLinkState' => new LinkState(LinkState::TYPE_HTTP, 301),
            ],
            'retry on bad response' => [
                'httpFixtures' => [
                    new Response(500),
                    new Response(200),
                ],
                'configuration' => new Configuration(),
                'urlHealthCheckerConfiguration' => new UrlHealthCheckerConfiguration([
                    UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => true,
                    UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
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
                'configuration' => new Configuration(),
                'urlHealthCheckerConfiguration' => new UrlHealthCheckerConfiguration([
                    UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                    UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                ]),
                'expectedLinkState' => new LinkState(LinkState::TYPE_CURL, 28),
            ],
            'http internal server error' => [
                'httpFixtures' => array_fill(0, 6, new Response(500)),
                'configuration' => new Configuration(),
                'urlHealthCheckerConfiguration' => new UrlHealthCheckerConfiguration([
                    UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                    UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
                ]),
                'expectedLinkState' => new LinkState(LinkState::TYPE_HTTP, 500),
            ],
            'http ok' => [
                'httpFixtures' => [
                    new Response(),
                ],
                'configuration' => new Configuration(),
                'urlHealthCheckerConfiguration' => new UrlHealthCheckerConfiguration([
                    UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
                    UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => ['GET'],
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

        $linkChecker = new LinkChecker(new Configuration(), $this->createUrlHealthChecker());

        $iterationCount = 10;

        for ($i = 0; $i < $iterationCount; $i++) {
            $linkChecker->getLinkState($url);
        }

        $this->assertEquals(1, $this->httpHistoryContainer->count());
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

        $linkChecker = new LinkChecker($configuration, $this->createUrlHealthChecker());

        $linkChecker->getLinkState($url1);
        $linkChecker->getLinkState($url2);
        $linkChecker->getLinkState($url3);

        $this->assertEquals(1, $this->httpHistoryContainer->count());
    }
}
