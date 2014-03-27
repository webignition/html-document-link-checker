<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\WebResource\WebPage\WebPage;

class CycleUserAgentsTest extends BaseTest {
    
    public function testCycleUserAgentsOnError() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 404 Not Found',   
            'HTTP/1.1 200 Ok'         
        ));
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example10', 'http://example.com/foo'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        $checker->getConfiguration()->setUserAgents(array('foo', 'bar'));
        
        $this->assertEquals(0, count($checker->getErrored()));
    }  
    
    
}