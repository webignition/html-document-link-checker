<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\Configuration\GetUserAgentSelectionForRequest;

use webignition\Tests\HtmlDocument\LinkChecker\Configuration\ConfigurationTest;

class GetUserAgentSelectionForRequestTest extends ConfigurationTest {
    
    public function testGetReturnsSelectionSet() {        
        $userAgents = array(
            'foo',
            'bar'
        );
        
        $this->getConfiguration()->setUserAgents($userAgents);
        $this->assertEquals($userAgents, $this->getConfiguration()->getUserAgentSelectionForRequest());
    }
    
}