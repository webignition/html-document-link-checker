<?php

namespace webignition\Tests\HtmlDocumentLinkChecker;

use webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocumentLinkChecker\LinkCheckResult;
use webignition\HtmlDocumentLinkChecker\LinkState;

class RequestOptionsTest extends BaseTest {
    
    public function testSettingLowTimeoutCausesTimeout() {        
        $webPage = new WebPage();
        $webPage->setUrl('http://www.americanexpress.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example14'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        $checker->setHttpMethodList(array('GET'));
        $requestOptions = $checker->getRequestOptions();
        $requestOptions['timeout'] = 0.001;
        $requestOptions['connect_timeout'] = 0.001;
        $checker->setRequestOptions($requestOptions);
        
        $this->assertEquals(array(
            new LinkCheckResult(
                    'https://online.americanexpress.com/myca/logon/us/action/LogLogoffHandler?Face=en_US&inav=Logout&request_type=LogLogoffHandler',
                    '<a id="Logout" title="Log out from the account" href="https://online.americanexpress.com/myca/logon/us/action/LogLogoffHandler?request_type=LogLogoffHandler&amp;Face=en_US&amp;inav=Logout" class="iNavLinkLogout">Log Out</a>',
                    new LinkState(LinkState::TYPE_CURL, 28)),
        ), $checker->getAll());
    }
    
}