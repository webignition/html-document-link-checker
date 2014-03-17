<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\CookiesTest;

class WithDomainNoPathNoSecureTest extends CookiesTest { 
    
    protected function getCookies() {
        return array(
            array(
                'domain' => '.example.com',
                'name' => 'name1',
                'value' => 'value1'
            )                       
        );         
    }
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {
        $requests = array();
        
        foreach ($this->getHttpHistory()->getAll() as $httpTransaction) {            
            if ($httpTransaction['request']->getUrl() != 'http://anotherexample.com/') {
                $requests[] = $httpTransaction['request'];
            }
        }
        
        return $requests;
    }    
    
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
         $requests = array();
        
        foreach ($this->getHttpHistory()->getAll() as $httpTransaction) {
            if ($httpTransaction['request']->getUrl() == 'http://anotherexample.com/') {
                $requests[] = $httpTransaction['request'];
            }
        }
        
        return $requests;
    }
    
    
    
    
}