<?php

namespace webignition\HtmlDocumentLinkChecker\Tests;

use webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker;
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
        $this->loadHttpFixtures(array(
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
            'http://example.com/relative-path' => 200,
            'http://example.com/root-relative-path' => 200,
            'http://example.com/protocol-relative-same-host' => 200,
            'http://another.example.com/protocol-relative-same-host' => 200,
            'http://example.com/#fragment-only' => 200,
            'http://www.youtube.com/example' => 200,
            'http://blog.example.com/' => 200,
            'http://twitter.com/example' => 200,
        ), $checker->getLinkStates());         
    }  
    
    public function testWithAll404() {
        $this->loadHttpFixtures(array(
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
            'http://example.com/relative-path' => 404,
            'http://example.com/root-relative-path' => 404,
            'http://example.com/protocol-relative-same-host' => 404,
            'http://another.example.com/protocol-relative-same-host' => 404,
            'http://example.com/#fragment-only' => 404,
            'http://www.youtube.com/example' => 404,
            'http://blog.example.com/' => 404,
            'http://twitter.com/example' => 404,
        ), $checker->getLinkStates());         
    } 
    
    public function testWithAll503() {
        $this->loadHttpFixtures(array(
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
            'http://example.com/relative-path' => 503,
            'http://example.com/root-relative-path' => 503,
            'http://example.com/protocol-relative-same-host' => 503,
            'http://another.example.com/protocol-relative-same-host' => 503,
            'http://example.com/#fragment-only' => 503,
            'http://www.youtube.com/example' => 503,
            'http://blog.example.com/' => 503,
            'http://twitter.com/example' => 503,
        ), $checker->getLinkStates());         
    }
    
    
    public function testWithVariedStatusCodes() {
        $this->loadHttpFixtures(array(
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
            'http://example.com/relative-path' => 200,
            'http://example.com/root-relative-path' => 404,
            'http://example.com/protocol-relative-same-host' => 500,
            'http://another.example.com/protocol-relative-same-host' => 410,
            'http://example.com/#fragment-only' => 200,
            'http://www.youtube.com/example' => 200,
            'http://blog.example.com/' => 404,
            'http://twitter.com/example' => 400,
        ), $checker->getLinkStates());         
    }    
    
    
}