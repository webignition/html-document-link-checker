<?php

namespace webignition\HtmlDocumentLinkChecker\Tests;

use webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker;
use webignition\WebResource\WebPage\WebPage;

class GetLinksByStatusCodeTest extends BaseTest {
    
    public function testWithNoWebPage() {
        $checker = new HtmlDocumentLinkChecker();        
        $this->assertEquals(array(), $checker->getLinksByStatusCode(200));     
        $this->assertEquals(array(), $checker->getLinksByStatusCode(404));     
        $this->assertEquals(array(), $checker->getLinksByStatusCode(500));     
    }
    
    public function testWithNoLinks() {
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example03'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(), $checker->getLinksByStatusCode(200));     
        $this->assertEquals(array(), $checker->getLinksByStatusCode(404));     
        $this->assertEquals(array(), $checker->getLinksByStatusCode(500));
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

        $this->assertEquals(array(), $checker->getLinksByStatusCode(404));         
        $this->assertEquals(array(), $checker->getLinksByStatusCode(500));
        
        $this->assertEquals(array(
            'http://example.com/relative-path',
            'http://example.com/root-relative-path',
            'http://example.com/protocol-relative-same-host',
            'http://another.example.com/protocol-relative-same-host',
            'http://example.com/#fragment-only',
            'http://www.youtube.com/example',
            'http://blog.example.com/',
            'http://twitter.com/example',
        ), $checker->getLinksByStatusCode(200));         
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
        
        $this->assertEquals(array(), $checker->getLinksByStatusCode(200));         
        $this->assertEquals(array(), $checker->getLinksByStatusCode(500));        
        
        $this->assertEquals(array(
            'http://example.com/relative-path',
            'http://example.com/root-relative-path',
            'http://example.com/protocol-relative-same-host',
            'http://another.example.com/protocol-relative-same-host',
            'http://example.com/#fragment-only',
            'http://www.youtube.com/example',
            'http://blog.example.com/',
            'http://twitter.com/example',
        ), $checker->getLinksByStatusCode(404));         
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
        
        $this->assertEquals(array(), $checker->getLinksByStatusCode(200));         
        $this->assertEquals(array(), $checker->getLinksByStatusCode(500));         
        
        $this->assertEquals(array(
            'http://example.com/relative-path',
            'http://example.com/root-relative-path',
            'http://example.com/protocol-relative-same-host',
            'http://another.example.com/protocol-relative-same-host',
            'http://example.com/#fragment-only',
            'http://www.youtube.com/example',
            'http://blog.example.com/',
            'http://twitter.com/example',
        ), $checker->getLinksByStatusCode(503));         
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
            'http://example.com/relative-path',
            'http://example.com/#fragment-only',
            'http://www.youtube.com/example'
        ), $checker->getLinksByStatusCode(200)); 
        
        $this->assertEquals(array(
            'http://example.com/root-relative-path',
            'http://blog.example.com/'
        ), $checker->getLinksByStatusCode(404)); 
        
        $this->assertEquals(array(
            'http://example.com/protocol-relative-same-host'
        ), $checker->getLinksByStatusCode(500)); 
        

        $this->assertEquals(array(
            'http://another.example.com/protocol-relative-same-host',
        ), $checker->getLinksByStatusCode(410));         
        

        $this->assertEquals(array(
            'http://twitter.com/example',
        ), $checker->getLinksByStatusCode(400));      
    }    
    
    
}