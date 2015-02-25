<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\CookiesTest;

use GuzzleHttp\Message\RequestInterface as HttpRequest;

class NoDomainNoPathNoSecureTest extends CookiesTest { 
    
    protected function getCookies() {
        return array(
            array(
                'Name' => 'name1',
                'Value' => 'value1'
            )                       
        );         
    }
    
    /**
     * 
     * @return HttpRequest[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {
        return array();
    }    
    
    
    /**
     *
     * @return HttpRequest[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        return $this->getHttpHistory()->getRequests();
    }
    
    
}