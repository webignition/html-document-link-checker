<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\Configuration;

class CookiesTest extends ConfigurationTest {
    
    public function testGetDefaultCookies() {
        $this->assertEquals(array(), $this->getConfiguration()->getCookies());
    }    
    
    public function testSetReturnsSelf() {
        $this->assertEquals($this->getConfiguration(), $this->getConfiguration()->setCookies(array()));
    }
    
    public function testSetGetCookies() {        
        $cookies = array(
            array(
                'name' => 'name1',
                'value' => 'value1'
            ),
            array(
                'domain' => '.example.com',
                'name' => 'name2',
                'value' => 'value2'
            ),
            array(
                'domain' => '.example.com',
                'secure' => true,
                'name' => 'name3',
                'value' => 'value3'
            )                        
        );
        
        $this->getConfiguration()->setCookies($cookies);
        $this->assertEquals($cookies, $this->getConfiguration()->getCookies());
    }
    
}