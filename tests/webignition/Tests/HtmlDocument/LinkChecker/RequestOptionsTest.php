<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocument\LinkChecker\LinkResult;
use webignition\HtmlDocument\LinkChecker\LinkState;

class RequestOptionsTest extends BaseTest {
    
    public function testSettingLowTimeoutCausesTimeout() {        
        $webPage = new WebPage();
        $webPage->setUrl('http://www.americanexpress.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example14'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);

        $requestOptions = $checker->getRequestOptions();
        $requestOptions['timeout'] = 0.001;
        $requestOptions['connect_timeout'] = 0.001;
        $checker->setRequestOptions($requestOptions);
        
        $this->assertEquals(array(
            new LinkResult(
                    'https://online.americanexpress.com/myca/logon/us/action/LogLogoffHandler?Face=en_US&inav=Logout&request_type=LogLogoffHandler',
                    '<a id="Logout" title="Log out from the account" href="https://online.americanexpress.com/myca/logon/us/action/LogLogoffHandler?request_type=LogLogoffHandler&amp;Face=en_US&amp;inav=Logout" class="iNavLinkLogout">Log Out</a>',
                    new LinkState(LinkState::TYPE_CURL, 28)),
        ), $checker->getAll());
    }
    
}