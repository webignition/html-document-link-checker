<?php

namespace webignition\HtmlDocumentLinkChecker\Tests;

use webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker;
use webignition\WebResource\WebPage\WebPage;

class RetryTest extends BaseTest {
    
    public function testRetryAndSucceed() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',            
            'HTTP/1.1 200 Ok',            
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com');
        $webPage->setContent($this->getHtmlDocumentFixture('example10'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        $checker->setHttpMethodList(array('GET'));
        $checker->setUserAgents(array('bar'));
        
        $this->assertEquals(0, count($checker->getErrored()));
    }     
    
    public function testRetryAndFail() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',            
            'HTTP/1.1 404 Not Found'     
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com');
        $webPage->setContent($this->getHtmlDocumentFixture('example10'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        $checker->setHttpMethodList(array('GET'));
        $checker->setUserAgents(array('bar'));
        
        $this->assertEquals(1, count($checker->getErrored()));        
    }
    
    
    public function testWithRetryDisabled() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 500 Internal Server Error'     
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com');
        $webPage->setContent($this->getHtmlDocumentFixture('example10'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        $checker->setHttpMethodList(array('GET'));
        $checker->setRetryOnBadResponse(false);
        
        $erroredLinks = $checker->getErrored();        
        
        $this->assertEquals(1, count($checker->getErrored()));
        $this->assertEquals(500, $erroredLinks[0]->getLinkState()->getState());
        
    }
    
    
}