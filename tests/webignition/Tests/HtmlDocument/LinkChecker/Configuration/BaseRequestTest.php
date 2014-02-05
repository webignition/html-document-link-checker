<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\Configuration;

class BaseRequestTest extends ConfigurationTest {
    
    public function testGetDefaultBaseRequest() {
        $this->assertEquals($this->getHttpClient()->get(), $this->getConfiguration()->getBaseRequest());
    }    
    
    public function testSetReturnsSelf() {
        $this->assertEquals($this->getConfiguration(), $this->getConfiguration()->setBaseRequest($this->getHttpClient()->get()));
    }
    
    public function testSetGetBaseRequest() {        
        $baseRequest = $this->getHttpClient()->get();
        $baseRequest->setAuth('example_user', 'example_password');
        
        $this->getConfiguration()->setBaseRequest($baseRequest);
        
        $this->assertEquals('example_user', $this->getConfiguration()->getBaseRequest()->getUsername());
        $this->assertEquals($baseRequest->getUsername(), $this->getConfiguration()->getBaseRequest()->getUsername());
    }
    
}