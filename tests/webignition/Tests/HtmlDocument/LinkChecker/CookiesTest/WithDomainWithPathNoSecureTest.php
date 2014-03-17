<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\CookiesTest;

class WithDomainWithPathNoSecureTest extends CookiesTest { 
    
    protected function getCookies() {
        return array(
            array(
                'domain' => '.example.com',
                'path' => '/path',
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
            if ($httpTransaction['request']->getUrl() == 'http://example.com/path') {
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
            if ($httpTransaction['request']->getUrl() != 'http://example.com/path') {
                $requests[] = $httpTransaction['request'];
            }
        }
        
        return $requests;
    }
    
    
    
    
}