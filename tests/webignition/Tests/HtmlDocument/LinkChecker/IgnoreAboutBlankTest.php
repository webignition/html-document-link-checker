<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\WebResource\WebPage\WebPage;

class IgnoreAboutBlankTest extends BaseTest {
    
    public function testIgnoreAboutBlank() {        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example09', 'http://example.com/'));  
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(0, count($checker->getAll()));
    }  
    
    
}