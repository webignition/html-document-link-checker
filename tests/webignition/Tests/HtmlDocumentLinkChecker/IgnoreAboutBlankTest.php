<?php

namespace webignition\Tests\HtmlDocumentLinkChecker;

use webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker;
use webignition\WebResource\WebPage\WebPage;

class IgnoreAboutBlankTest extends BaseTest {
    
    public function testIgnoreAboutBlank() {        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com');
        $webPage->setContent($this->getHtmlDocumentFixture('example09'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        
        $this->assertEquals(0, count($checker->getAll()));
    }  
    
    
}