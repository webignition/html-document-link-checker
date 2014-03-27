<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\WebResource\WebPage\WebPage;

class ExcludeMailtoLinksTest extends BaseTest {
    
    public function testExcludeMailtoLinks() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 200 Ok'
        ));    
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example05', 'http://example.com'));        
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(), $checker->getErrored());          
    }
    
    
}