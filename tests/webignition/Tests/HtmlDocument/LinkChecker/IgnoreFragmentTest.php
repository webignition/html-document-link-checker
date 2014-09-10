<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\WebResource\WebPage\WebPage;

class IgnoreFragmentTest extends BaseTest {
    
    public function testReuseLinkState() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 200',
            'HTTP/1.1 200',
            'HTTP/1.1 200',
        ));      
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example17', 'http://example.com'));
        
        $checker = $this->getDefaultChecker();
        $checker->getConfiguration()->enableIgnoreFragmentInUrlComparison();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(6, count($checker->getAll()));
    }  
    
    
}