<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\Configuration;

use webignition\Tests\HtmlDocument\LinkChecker\Configuration\ConfigurationTest;

class SchemesToExcludeTest extends ConfigurationTest {
    
    public function testGetDefault() {        
        $this->assertEquals(array(
            'mailto',
            'about',
            'javascript',
            'ftp',
            'tel'
        ), $this->getConfiguration()->getSchemesToExclude());
    }
    
    public function testSetReturnsSelf() {
        $this->assertEquals($this->getConfiguration(), $this->getConfiguration()->setSchemesToExclude(array()));
    }
    
    public function testSetGetUserAgents() {     
        $schemes = array(
            'foo',
            'bar'
        );
        
        $this->assertEquals($schemes, $this->getConfiguration()->setSchemesToExclude($schemes)->getSchemesToExclude());
    }
    
}