<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\CookiesTest;

use GuzzleHttp\Message\RequestInterface as HttpRequest;

class WithDomainNoPathWithSecureTest extends CookiesTest { 
    
    protected function getCookies() {
        return array(
            array(
                'Domain' => '.example.com',
                'Secure' => true,
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
        return $this->getHttpHistory()->getLastRequest();
    }    
    
    
    /**
     *
     * @return HttpRequest[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        $requests = array();
        
        foreach ($this->getHttpHistory()->getRequests() as $request) {
            if ($request->getUrl() != 'https://example.com/') {
                $requests[] = $request;
            }
        }
        
        return $requests;
    }
    
}