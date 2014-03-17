<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\CookiesTest;

class WithDomainNoPathWithSecureTest extends CookiesTest { 
    
    protected function getCookies() {
        return array(
            array(
                'domain' => '.example.com',
                'secure' => true,
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
        return $this->getHttpHistory()->getLastRequest();
    }    
    
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        $requests = array();
        
        foreach ($this->getHttpHistory()->getAll() as $httpTransaction) {
            if ($httpTransaction['request']->getUrl() != 'https://example.com/') {
                $requests[] = $httpTransaction['request'];
            }
        }
        
        return $requests;
    }
    
}