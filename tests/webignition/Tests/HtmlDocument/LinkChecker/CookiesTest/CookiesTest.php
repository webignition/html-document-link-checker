<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\CookiesTest;

use webignition\WebResource\WebPage\WebPage;
use webignition\Tests\HtmlDocument\LinkChecker\BaseTest;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Guzzle\Plugin\Cookie\Cookie;

abstract class CookiesTest extends BaseTest {
    
    protected $wrapper;
    
    /**
     * 
     * @return array
     */
    abstract protected function getCookies();
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */    
    abstract protected function getExpectedRequestsOnWhichCookiesShouldBeSet();
    
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
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

        $cookieJar = new ArrayCookieJar();

        foreach ($this->getCookies() as $cookieData) {
            $cookieJar->add(new Cookie($cookieData));
        }

        $cookiePlugin = new CookiePlugin($cookieJar);

        $this->getHttpClient()->addSubscriber($cookiePlugin);
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $checker->getAll();
    }
    
    
    public function testCookiesAreSetOnExpectedRequests() {
        foreach ($this->getExpectedRequestsOnWhichCookiesShouldBeSet() as $request) {            
            $this->assertEquals($this->getExpectedCookieValues(), $request->getCookies());
        }
    }
    
    
    public function testCookiesAreNotSetOnExpectedRequests() {        
        foreach ($this->getExpectedRequestsOnWhichCookiesShouldNotBeSet() as $request) {            
            $this->assertEquals(array(), $request->getCookies());
        }
    }    
    

    /**
     * 
     * @return array
     */
    private function getExpectedCookieValues() {
        $nameValueArray = array();
        
        foreach ($this->getCookies() as $cookie) {
            $nameValueArray[$cookie['name']] = $cookie['value'];
        }
        
        return $nameValueArray;
    }
    
}