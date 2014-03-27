<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\WebResource\WebPage\WebPage;

class RetryTest extends BaseTest {
    
    public function testRetryAndSucceed() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',            
            'HTTP/1.1 200 Ok',            
        ));
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example10', 'http://example.com'));  
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        $checker->getConfiguration()->enableRetryOnBadResponse();
        
        $this->assertEquals(0, count($checker->getErrored()));
    }     
    
    public function testRetryAndFail() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',            
            'HTTP/1.1 404 Not Found'     
        ));
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example10', 'http://example.com'));  
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        $checker->getConfiguration()->enableRetryOnBadResponse();
        
        $this->assertEquals(1, count($checker->getErrored()));        
    }
    
    
    public function testWithRetryDisabled() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 500 Internal Server Error'     
        ));
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example10', 'http://example.com'));  
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        $checker->getConfiguration()->disableRetryOnBadResponse();
        
        $erroredLinks = $checker->getErrored();        
        
        $this->assertEquals(1, count($checker->getErrored()));
        $this->assertEquals(500, $erroredLinks[0]->getLinkState()->getState());
        
    }
    
    
}