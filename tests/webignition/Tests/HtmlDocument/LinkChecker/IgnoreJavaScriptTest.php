<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\WebResource\WebPage\WebPage;

class IgnoreJavaScriptTest extends BaseTest {
    
    public function testIgnoreJavascriptColonAnything() {        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example11', 'http://example.com/'));  
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(0, count($checker->getAll()));
    }  
    
    
}