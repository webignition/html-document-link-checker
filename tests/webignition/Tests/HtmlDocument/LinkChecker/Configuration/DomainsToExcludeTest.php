<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\Configuration;

use webignition\Tests\HtmlDocument\LinkChecker\Configuration\ConfigurationTest;

class DomainsToExcludeTest extends ConfigurationTest {
    
    public function testGetDefault() {        
        $this->assertEquals(array(), $this->getConfiguration()->getDomainsToExclude());
    }
    
    public function testSetReturnsSelf() {
        $this->assertEquals($this->getConfiguration(), $this->getConfiguration()->setDomainsToExclude(array()));
    }
    
    public function testSetGetUserAgents() {     
        $domains = array(
            'foo',
            'bar'
        );
        
        $this->assertEquals($domains, $this->getConfiguration()->setDomainsToExclude($domains)->getDomainsToExclude());
    }
    
}