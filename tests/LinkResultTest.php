<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\HtmlDocument\LinkChecker\LinkResult;
use webignition\UrlHealthChecker\LinkState;

class LinkResultTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param string $url
     * @param string $context
     * @param LinkState $linkState
     */
    public function testCreate(string $url, string $context, LinkState $linkState)
    {
        $linkResult = new LinkResult($url, $context, $linkState);

        $this->assertEquals($url, $linkResult->getUrl());
        $this->assertEquals($context, $linkResult->getContext());
        $this->assertEquals($linkState, $linkResult->getLinkState());
    }

    public function createDataProvider(): array
    {
        return [
            'default' => [
                'url' => 'http://example.com',
                'context' => '<a href="http://example.com">Example</a>',
                'linkState' => new LinkState('http', 200),
            ],
        ];
    }
}
