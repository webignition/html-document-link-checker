<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\HtmlDocument\LinkChecker\LinkResult;
use webignition\UrlHealthChecker\LinkState;
use webignition\WebResource\WebPage\WebPage;

class TooManyRedirectsTest extends BaseTest {
    
    public function testReuseLinkState() {
        $this->loadHttpClientFixtures(array(
            "HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect1\r\nContent-Length: 0\r\n\r\n",
            "HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect2\r\nContent-Length: 0\r\n\r\n",
            "HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect3\r\nContent-Length: 0\r\n\r\n",
            "HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect4\r\nContent-Length: 0\r\n\r\n",
            "HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect5\r\nContent-Length: 0\r\n\r\n",
            "HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect1\r\nContent-Length: 0\r\n\r\n",
        ));
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example10', 'http://example.com'));  
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(
            new LinkResult('http://example.com/', '<a href="http://example.com/">Example no subdomain</a>', new LinkState(LinkState::TYPE_HTTP, 301))            
        ), $checker->getAll());
    }  
    
    
}