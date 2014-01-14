<?php

namespace webignition\Tests\HtmlDocumentLinkChecker;

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
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        $checker->setRetryOnBadResponse(true);
        
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
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        $checker->setRetryOnBadResponse(true);
        
        $this->assertEquals(1, count($checker->getErrored()));        
    }
    
    
    public function testWithRetryDisabled() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 500 Internal Server Error'     
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com');
        $webPage->setContent($this->getHtmlDocumentFixture('example10'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        $checker->setRetryOnBadResponse(false);
        
        $erroredLinks = $checker->getErrored();        
        
        $this->assertEquals(1, count($checker->getErrored()));
        $this->assertEquals(500, $erroredLinks[0]->getLinkState()->getState());
        
    }
    
    
}