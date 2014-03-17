<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\CookiesTest;

class NoDomainNoPathNoSecureTest extends CookiesTest { 
    
    protected function getCookies() {
        return array(
            array(
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
        return array();
    }    
    
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        $requests = array();
        
        foreach ($this->getHttpHistory()->getAll() as $httpTransaction) {
            $requests[] = $httpTransaction['request'];
        }
        
        return $requests;
    }
    
    
    
    
}