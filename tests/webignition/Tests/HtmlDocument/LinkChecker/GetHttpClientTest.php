<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\HtmlDocument\LinkChecker\LinkChecker;

class GetHttpClientTest extends BaseTest {
    
    const TEST_USER_AGENT = 'Test User Agent';
    
    public function testWithoutSetting() {      
        $linkChecker = new LinkChecker();
        $userAgentHeaderValues = $linkChecker->getHttpClient()->get()->getHeader('user-agent')->toArray();      
        $this->assertNotEquals(self::TEST_USER_AGENT, $userAgentHeaderValues[0]);
    }
    
    public function testWithSetting() {      
        $httpClient = new \Guzzle\Http\Client();
        $httpClient->setUserAgent(self::TEST_USER_AGENT);
        
        $linkChecker = new LinkChecker();
        $linkChecker->setHttpClient($httpClient);
        $userAgentHeaderValues = $linkChecker->getHttpClient()->get()->getHeader('user-agent')->toArray();      
        $this->assertEquals(self::TEST_USER_AGENT, $userAgentHeaderValues[0]);
    }    
    
    
}