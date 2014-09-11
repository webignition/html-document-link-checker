<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\HtmlDocument\LinkChecker\LinkResult;
use webignition\UrlHealthChecker\LinkState;
use webignition\WebResource\WebPage\WebPage;

class GetWorkingLinksTest extends BaseTest {
    
    public function testWithNoWebPage() {
        $checker = $this->getDefaultChecker();        
        $this->assertEquals(array(), $checker->getWorking());         
    }
    
    public function testWithNoLinks() {
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example03', 'http://example.com/'));        
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(), $checker->getWorking());  
    }    
    
    public function testWithVariedHttpStatusCodes() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 404 Not Found',          
            'HTTP/1.1 500 Internal Server Error',          
            'HTTP/1.1 410 Gone',            
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok',        
        ));
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example01', 'http://example.com/'));        
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(
            new LinkResult('http://www.youtube.com/example', '<a href="http://www.youtube.com/example"><img src="/images/youtube.png"></a>', new LinkState(LinkState::TYPE_HTTP, 200)),
            new LinkResult('http://twitter.com/example', '<a href="http://twitter.com/example"><img src="/images/twitter.png"></a>', new LinkState(LinkState::TYPE_HTTP, 200)),
            new LinkResult('http://example.com/images/twitter.png', '<img src="/images/twitter.png">', new LinkState(LinkState::TYPE_HTTP, 200)),
        ), $checker->getWorking());        
    }

    
    
    public function testWithVariedCurlCodesCodes() {
        $curl6Exception = new \Guzzle\Http\Exception\CurlException();
        $curl6Exception->setError('Couldn\'t resolve host. The given remote host was not resolved.', 6);        
        
        $curl28Exception = new \Guzzle\Http\Exception\CurlException();
        $curl28Exception->setError('Operation timeout. The specified time-out period was reached according to the conditions.', 28);        
        
        $curl55Exception = new \Guzzle\Http\Exception\CurlException();
        $curl55Exception->setError('Failed sending network data.', 55);
        
        $this->loadHttpClientFixtures(array(
            $curl6Exception,
            $curl28Exception,
            $curl28Exception,
            $curl55Exception,
            $curl6Exception,
            $curl55Exception
        ));
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example01', 'http://example.com/'));          
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(), $checker->getWorking());    
    }   
    
    
    public function testWithMixedHttpStatusCodesAndCurlCodes() {
        $curl6Exception = new \Guzzle\Http\Exception\CurlException();
        $curl6Exception->setError('Couldn\'t resolve host. The given remote host was not resolved.', 6);        
        
        $curl28Exception = new \Guzzle\Http\Exception\CurlException();
        $curl28Exception->setError('Operation timeout. The specified time-out period was reached according to the conditions.', 28);        
        
        $curl55Exception = new \Guzzle\Http\Exception\CurlException();
        $curl55Exception->setError('Failed sending network data.', 55);
        
        $this->loadHttpClientFixtures(array(
            $curl6Exception,
            'HTTP/1.1 200 Ok',
            $curl28Exception,
            'HTTP/1.1 500 Internal Server Error',         
            'HTTP/1.1 400 Bad Request',           
            'HTTP/1.1 200 Ok',         
        ));      
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example01', 'http://example.com/'));  
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(
            new LinkResult('http://example.com/images/youtube.png', '<img src="/images/youtube.png">', new LinkState(LinkState::TYPE_HTTP, 200)),
            new LinkResult('http://example.com/images/twitter.png', '<img src="/images/twitter.png">', new LinkState(LinkState::TYPE_HTTP, 200)),
        ), $checker->getWorking());        
    }    
    
    
}