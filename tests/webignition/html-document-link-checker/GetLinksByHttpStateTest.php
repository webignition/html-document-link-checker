<?php

namespace webignition\HtmlDocumentLinkChecker\Tests;

use webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker;
use webignition\WebResource\WebPage\WebPage;

class GetLinksByHttpStateTest extends BaseTest {
    
    public function testWithNoWebPage() {
        $checker = new HtmlDocumentLinkChecker();        
        $this->assertEquals(array(), $checker->getLinksByHttpState(200));     
        $this->assertEquals(array(), $checker->getLinksByHttpState(404));     
        $this->assertEquals(array(), $checker->getLinksByHttpState(500));     
    }
    
    public function testWithNoLinks() {
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example03'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(), $checker->getLinksByHttpState(200));     
        $this->assertEquals(array(), $checker->getLinksByHttpState(404));     
        $this->assertEquals(array(), $checker->getLinksByHttpState(500));
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
            'http://example.com/relative-path',
            'http://example.com/#fragment-only',
            'http://www.youtube.com/example'
        ), $checker->getLinksByHttpState(200)); 
        
        $this->assertEquals(array(
            'http://example.com/root-relative-path',
            'http://blog.example.com/'
        ), $checker->getLinksByHttpState(404)); 
        
        $this->assertEquals(array(
            'http://example.com/protocol-relative-same-host'
        ), $checker->getLinksByHttpState(500)); 
        

        $this->assertEquals(array(
            'http://another.example.com/protocol-relative-same-host',
        ), $checker->getLinksByHttpState(410));         
        

        $this->assertEquals(array(
            'http://twitter.com/example',
        ), $checker->getLinksByHttpState(400));      
    }    
    
    
}