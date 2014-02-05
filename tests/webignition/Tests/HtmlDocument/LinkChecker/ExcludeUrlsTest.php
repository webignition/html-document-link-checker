<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\WebResource\WebPage\WebPage;

class ExcludeUrlsTest extends BaseTest {
    
    public function testExcludeDomains() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 200 Ok'            
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com');
        $webPage->setContent($this->getHtmlDocumentFixture('example08'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        $checker->setUrlsToExclude(array(
            'http://example.com/foo',
            'http://example.com/bar'
        ));
        
        $this->assertEquals(1, count($checker->getAll()));
    }  
    
    
}