<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\WebResource\WebPage\WebPage;

class IgnoreJavaScriptTest extends BaseTest {
    
    public function testIgnoreJavascriptColonAnything() {        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com');
        $webPage->setContent($this->getHtmlDocumentFixture('example11'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(0, count($checker->getAll()));
    }  
    
    
}