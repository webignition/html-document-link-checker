<?php

namespace webignition\HtmlDocumentLinkChecker\Tests;

use webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker;
use webignition\HtmlDocumentLinkChecker\LinkState;
use webignition\WebResource\WebPage\WebPage;

class GetLinkStatesTest extends BaseTest {
    
    public function testWithNoWebPage() {
        $checker = new HtmlDocumentLinkChecker();
        
        $this->assertEquals(array(), $checker->getLinkStates());     
    }
    
    public function testWithNoLinks() {
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example03'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(), $checker->getLinkStates()); 
    }
    
    public function testWithAll200() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 200 OK',
            'HTTP/1.1 200 OK',
            'HTTP/1.1 200 OK',
            'HTTP/1.1 200 OK',
            'HTTP/1.1 200 OK',
            'HTTP/1.1 200 OK',
            'HTTP/1.1 200 OK',
            'HTTP/1.1 200 OK'
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example01'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        
        $this->assertEquals(array(
            'http://example.com/relative-path' => new LinkState('http', 200),
            'http://example.com/root-relative-path' => new LinkState('http', 200),
            'http://example.com/protocol-relative-same-host' => new LinkState('http', 200),
            'http://another.example.com/protocol-relative-same-host' => new LinkState('http', 200),
            'http://example.com/#fragment-only' => new LinkState('http', 200),
            'http://www.youtube.com/example' => new LinkState('http', 200),
            'http://blog.example.com/' => new LinkState('http', 200),
            'http://twitter.com/example' => new LinkState('http', 200),
        ), $checker->getLinkStates());         
    }  
    
    public function testWithAll404() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found'
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example01'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        
        $this->assertEquals(array(
            'http://example.com/relative-path' => new LinkState('http', 404),
            'http://example.com/root-relative-path' => new LinkState('http', 404),
            'http://example.com/protocol-relative-same-host' => new LinkState('http', 404),
            'http://another.example.com/protocol-relative-same-host' => new LinkState('http', 404),
            'http://example.com/#fragment-only' => new LinkState('http', 404),
            'http://www.youtube.com/example' => new LinkState('http', 404),
            'http://blog.example.com/' => new LinkState('http', 404),
            'http://twitter.com/example' => new LinkState('http', 404),
        ), $checker->getLinkStates());         
    } 
    
    public function testWithAll503() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 503 Service Unavailable',
            'HTTP/1.1 503 Service Unavailable',
            'HTTP/1.1 503 Service Unavailable',
            'HTTP/1.1 503 Service Unavailable',
            'HTTP/1.1 503 Service Unavailable',
            'HTTP/1.1 503 Service Unavailable',
            'HTTP/1.1 503 Service Unavailable',
            'HTTP/1.1 503 Service Unavailable'
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example01'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        
        $this->assertEquals(array(
            'http://example.com/relative-path' => new LinkState('http', 503),
            'http://example.com/root-relative-path' => new LinkState('http', 503),
            'http://example.com/protocol-relative-same-host' => new LinkState('http', 503),
            'http://another.example.com/protocol-relative-same-host' => new LinkState('http', 503),
            'http://example.com/#fragment-only' => new LinkState('http', 503),
            'http://www.youtube.com/example' => new LinkState('http', 503),
            'http://blog.example.com/' => new LinkState('http', 503),
            'http://twitter.com/example' => new LinkState('http', 503),
        ), $checker->getLinkStates());         
    }
    
    
    public function testWithVariedStatusCodes() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 500 Internal Server Error',
            'HTTP/1.1 410 Gone',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 400 Bad Request'
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example01'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        
        $this->assertEquals(array(
            'http://example.com/relative-path' => new LinkState('http', 200),
            'http://example.com/root-relative-path' => new LinkState('http', 404),
            'http://example.com/protocol-relative-same-host' => new LinkState('http', 500),
            'http://another.example.com/protocol-relative-same-host' => new LinkState('http', 410),
            'http://example.com/#fragment-only' => new LinkState('http', 200),
            'http://www.youtube.com/example' => new LinkState('http', 200),
            'http://blog.example.com/' => new LinkState('http', 404),
            'http://twitter.com/example' => new LinkState('http', 400),
        ), $checker->getLinkStates());         
    }    
    
    
}