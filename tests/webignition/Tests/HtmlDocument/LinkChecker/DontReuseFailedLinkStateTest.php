<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\HtmlDocument\LinkChecker\LinkResult;
use webignition\HtmlDocument\LinkChecker\LinkState;
use webignition\WebResource\WebPage\WebPage;

class DontReuseFailedLinkStateTest extends BaseTest {
    
    public function testReuseLinkState() {        
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 500 Internal Server Error',       
            'HTTP/1.1 200 Ok'
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com');
        $webPage->setContent($this->getHtmlDocumentFixture('example12'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(
            new LinkResult('http://example.com/', '<a href="http://example.com/">Example no subdomain 1</a>', new LinkState(LinkState::TYPE_HTTP, 500)),
            new LinkResult('http://example.com/', '<a href="http://example.com/">Example no subdomain 2</a>', new LinkState(LinkState::TYPE_HTTP, 200))                        
        ), $checker->getAll());
    }  
    
    
}