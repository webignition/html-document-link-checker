<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\Configuration;

use webignition\Tests\HtmlDocument\LinkChecker\Configuration\ConfigurationTest;

class UrlsToExcludeTest extends ConfigurationTest {
    
    public function testGetDefault() {        
        $this->assertEquals(array(), $this->getConfiguration()->getUrlsToExclude());
    }
    
    public function testSetReturnsSelf() {
        $this->assertEquals($this->getConfiguration(), $this->getConfiguration()->setUrlsToExclude(array()));
    }
    
    public function testSetGetUserAgents() {     
        $schemes = array(
            'foo',
            'bar'
        );
        
        $this->assertEquals($schemes, $this->getConfiguration()->setUrlsToExclude($schemes)->getUrlsToExclude());
    }
    
}