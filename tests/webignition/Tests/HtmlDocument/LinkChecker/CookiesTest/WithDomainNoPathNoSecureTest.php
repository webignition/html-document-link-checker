<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\CookiesTest;

use GuzzleHttp\Message\RequestInterface as HttpRequest;

class WithDomainNoPathNoSecureTest extends CookiesTest { 
    
    protected function getCookies() {
        return array(
            array(
                'Domain' => '.example.com',
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
        $requests = array();
        
        foreach ($this->getHttpHistory()->getRequests() as $request) {
            if ($request->getUrl() != 'http://anotherexample.com/') {
                $requests[] = $request;
            }
        }
        
        return $requests;
    }    
    
    
    /**
     *
     * @return HttpRequest[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
         $requests = array();
        
        foreach ($this->getHttpHistory()->getRequests() as $request) {
            if ($request->getUrl() == 'http://anotherexample.com/') {
                $requests[] = $request;
            }
        }
        
        return $requests;
    }
    
    
    
    
}