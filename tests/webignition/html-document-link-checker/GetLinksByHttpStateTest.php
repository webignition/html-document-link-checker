<?php

namespace webignition\HtmlDocumentLinkChecker\Tests;

use webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker;
use webignition\HtmlDocumentLinkChecker\LinkState;
use webignition\WebResource\WebPage\WebPage;

class GetLinksByHttpStateTest extends BaseTest {
    
    public function testWithNoWebPage() {
        $checker = new HtmlDocumentLinkChecker();        
        $this->assertEquals(array(), $checker->getByHttpState(200));     
        $this->assertEquals(array(), $checker->getByHttpState(404));     
        $this->assertEquals(array(), $checker->getByHttpState(500));     
    }
    
    public function testWithNoLinks() {
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example03'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(), $checker->getByHttpState(200));     
        $this->assertEquals(array(), $checker->getByHttpState(404));     
        $this->assertEquals(array(), $checker->getByHttpState(500));
    }    
    
    public function testWithVariedStatusCodes() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 500 Internal Server Error',
            'HTTP/1.1 500 Internal Server Error',
            'HTTP/1.1 410 Gone',
            'HTTP/1.1 410 Gone',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 400 Bad Request',
            'HTTP/1.1 400 Bad Request',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok',
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example01'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        
        $this->assertEquals(array(
            new LinkState('http', 200, 'http://example.com/relative-path', '<a href="relative-path">Relative Path</a>'),
            new LinkState('http', 200, 'http://example.com/#fragment-only', '<a href="#fragment-only">Fragment Only</a>'),
            new LinkState('http', 200, 'http://example.com/#fragment-only', '<a href="#fragment-only">Repeated Fragment Only (should be ignored)</a>'),
            new LinkState('http', 200, 'http://blog.example.com/', '<a href="http://blog.example.com"><img src="/images/blog.png"></a>'),
            new LinkState('http', 200, 'http://example.com/images/blog.png', '<img src="/images/blog.png">'),
            new LinkState('http', 200, 'http://twitter.com/example', '<a href="http://twitter.com/example"><img src="/images/twitter.png"></a>'),
            new LinkState('http', 200, 'http://example.com/images/twitter.png', '<img src="/images/twitter.png">'),
        ), $checker->getByHttpState(200));        
        
        $this->assertEquals(array(
            new LinkState('http', 404, 'http://example.com/root-relative-path', '<a href="/root-relative-path">Root Relative Path</a>'),
            new LinkState('http', 404, 'http://www.youtube.com/example', '<a href="http://www.youtube.com/example"><img src="/images/youtube.png"></a>'),
        ), $checker->getByHttpState(404));        
        
        $this->assertEquals(array(
            new LinkState('http', 500, 'http://example.com/protocol-relative-same-host', '<a href="//example.com/protocol-relative-same-host">Protocol Relative Same Host</a>'),
        ), $checker->getByHttpState(500));       
        
        $this->assertEquals(array(
            new LinkState('http', 410, 'http://another.example.com/protocol-relative-same-host', '<a href="//another.example.com/protocol-relative-same-host">Protocol Relative Different Host</a>'),
        ), $checker->getByHttpState(410));        

        $this->assertEquals(array(
            new LinkState('http', 400, 'http://example.com/images/youtube.png', '<img src="/images/youtube.png">'),
        ), $checker->getByHttpState(400));      
    }    
    
    
}