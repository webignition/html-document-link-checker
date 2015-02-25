<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\CookiesTest;

use webignition\WebResource\WebPage\WebPage;
use webignition\Tests\HtmlDocument\LinkChecker\BaseTest;
use GuzzleHttp\Subscriber\Cookie as HttpCookieSubscriber;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Message\RequestInterface as HttpRequest;

abstract class CookiesTest extends BaseTest {
    
    protected $wrapper;
    
    /**
     * 
     * @return array
     */
    abstract protected function getCookies();
    
    /**
     * 
     * @return HttpRequest[]
     */    
    abstract protected function getExpectedRequestsOnWhichCookiesShouldBeSet();
    
    
    /**
     * 
     * @return HttpRequest[]
     */    
    abstract protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet();    
    
    public function setUp() { 
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok'
        ));
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example16', 'http://example.com'));

        $cookieJar = new CookieJar();

        foreach ($this->getCookies() as $cookieData) {
            $cookieJar->setCookie(new SetCookie($cookieData));
        }

        $this->getHttpClient()->getEmitter()->attach(new HttpCookieSubscriber($cookieJar));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $checker->getAll();
    }
    
    
    public function testCookiesAreSetOnExpectedRequests() {
        foreach ($this->getExpectedRequestsOnWhichCookiesShouldBeSet() as $request) {            
            $this->assertEquals($this->getExpectedCookieValues(), $this->getRequestCookieValues($request));
        }
    }
    
    
    public function testCookiesAreNotSetOnExpectedRequests() {        
        foreach ($this->getExpectedRequestsOnWhichCookiesShouldNotBeSet() as $request) {            
            $this->assertEquals(array(), $this->getRequestCookieValues($request));
        }
    }    
    

    /**
     * 
     * @return array
     */
    private function getExpectedCookieValues() {
        $nameValueArray = array();
        
        foreach ($this->getCookies() as $cookie) {
            $nameValueArray[$cookie['Name']] = $cookie['Value'];
        }
        
        return $nameValueArray;
    }


    private function getRequestCookieValues(HttpRequest $request) {
        if (!$request->hasHeader('Cookie')) {
            return [];
        }

        $cookieStrings = explode(';', $request->getHeader('Cookie'));
        $values = [];

        foreach ($cookieStrings as $cookieString) {
            $cookieString = trim($cookieString);
            $currentValues = explode('=', $cookieString);
            $values[$currentValues[0]] = $currentValues[1];
        }

        return $values;
    }
    
}