<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\WebResource\WebPage\WebPage;

class ExcludeTelLinksTest extends BaseTest {
    
    public function testExcludeMailtoLinks() {
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example19', 'http://example.com'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals([], $checker->getErrored());
    }
    
    
}