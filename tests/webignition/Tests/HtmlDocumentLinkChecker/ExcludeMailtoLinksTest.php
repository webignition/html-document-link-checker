<?php

namespace webignition\Tests\HtmlDocumentLinkChecker;

use webignition\WebResource\WebPage\WebPage;

class ExcludeMailtoLinksTest extends BaseTest {
    
    public function testExcludeMailtoLinks() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 200 Ok'
        ));        
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example05'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(), $checker->getErrored());          
    }
    
    
}