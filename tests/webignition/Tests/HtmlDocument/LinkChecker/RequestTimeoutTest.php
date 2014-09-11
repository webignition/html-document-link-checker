<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocument\LinkChecker\LinkResult;
use webignition\UrlHealthChecker\LinkState;

class RequestTimeoutTest extends BaseTest {
    
    public function testSettingLowTimeoutCausesTimeout() {
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example14', 'http://example.com/'));  
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);

        $baseRequest = $this->getHttpClient()->createRequest('GET', $webPage->getHttpResponse()->getEffectiveUrl(), array(), null, array(
            'timeout'         => 0.001,
            'connect_timeout' => 0.001            
        )); 
        
        $checker->getUrlHealthChecker()->getConfiguration()->setBaseRequest($baseRequest);
        
        $this->assertEquals(array(
            new LinkResult(
                    'https://online.americanexpress.com/myca/logon/us/action/LogLogoffHandler?Face=en_US&inav=Logout&request_type=LogLogoffHandler',
                    '<a id="Logout" title="Log out from the account" href="https://online.americanexpress.com/myca/logon/us/action/LogLogoffHandler?request_type=LogLogoffHandler&amp;Face=en_US&amp;inav=Logout" class="iNavLinkLogout">Log Out</a>',
                    new LinkState(LinkState::TYPE_CURL, 28)),
        ), $checker->getAll());
    }
    
}