<?php

namespace webignition\HtmlDocumentLinkChecker\Tests;

use webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker;
use webignition\WebResource\WebPage\WebPage;

class CycleUserAgentsTest extends BaseTest {
    
    public function testCycleUserAgentsOnError() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 200 Ok'         
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com');
        $webPage->setContent($this->getHtmlDocumentFixture('example10'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        $checker->setHttpMethodList(array('GET'));
        $checker->setUserAgents(array('foo', 'bar'));
        
        $this->assertEquals(0, count($checker->getErrored()));
    }  
    
    
}