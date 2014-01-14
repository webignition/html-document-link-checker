<?php

namespace webignition\Tests\HtmlDocumentLinkChecker;

use webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker;

class GetHttpClientTest extends BaseTest {
    
    const TEST_USER_AGENT = 'Test User Agent';
    
    public function testWithoutSetting() {      
        $linkChecker = new HtmlDocumentLinkChecker();
        $userAgentHeaderValues = $linkChecker->getHttpClient()->get()->getHeader('user-agent')->toArray();      
        $this->assertNotEquals(self::TEST_USER_AGENT, $userAgentHeaderValues[0]);
    }
    
    public function testWithSetting() {      
        $httpClient = new \Guzzle\Http\Client();
        $httpClient->setUserAgent(self::TEST_USER_AGENT);
        
        $linkChecker = new HtmlDocumentLinkChecker();
        $linkChecker->setHttpClient($httpClient);
        $userAgentHeaderValues = $linkChecker->getHttpClient()->get()->getHeader('user-agent')->toArray();      
        $this->assertEquals(self::TEST_USER_AGENT, $userAgentHeaderValues[0]);
    }    
    
    
}