<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\WebResource\WebPage\WebPage;

class ExcludeFtpLinksTest extends BaseTest {
    
    public function testExcludeMailtoLinks() {
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example18', 'http://example.com'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals([], $checker->getErrored());
    }
    
    
}