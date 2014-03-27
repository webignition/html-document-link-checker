<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\WebResource\WebPage\WebPage;

class ExcludeDomainsTest extends BaseTest {
    
    public function testReuseLinkState() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 200 Ok'            
        ));
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example13', 'http://example.com'));

        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        $checker->getConfiguration()->setDomainsToExclude(array(
            'foo.com',
        ));
        
        $this->assertEquals(1, count($checker->getAll()));
    }  
    
    
}