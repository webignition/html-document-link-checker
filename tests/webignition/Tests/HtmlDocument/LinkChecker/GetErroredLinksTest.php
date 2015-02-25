<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\HtmlDocument\LinkChecker\LinkResult;
use webignition\UrlHealthChecker\LinkState;
use webignition\WebResource\WebPage\WebPage;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Message\Request;

class GetErroredLinksTest extends BaseTest {   

    
    public function testWithNoWebPage() {     
        $this->assertEquals(array(), $this->getDefaultChecker()->getErrored());         
    }
    
    public function testWithNoLinks() {
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example03', 'http://example.com'));        
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(), $checker->getErrored());  
    }    
    
    public function testWithVariedHttpStatusCodes() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 200',            
            'HTTP/1.1 404',
            'HTTP/1.1 500',
            'HTTP/1.1 410',            
            'HTTP/1.1 200',
            'HTTP/1.1 200',
        ));
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example01', 'http://example.com'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);      
        
        $this->assertEquals(array(
            new LinkResult('http://example.com/images/youtube.png', '<img src="/images/youtube.png">', new LinkState(LinkState::TYPE_HTTP, 404)),
            new LinkResult('http://blog.example.com/', '<a href="http://blog.example.com"><img src="/images/blog.png"></a>', new LinkState(LinkState::TYPE_HTTP, 500)),
            new LinkResult('http://example.com/images/blog.png', '<img src="/images/blog.png">', new LinkState(LinkState::TYPE_HTTP, 410)),
        ), $checker->getErrored());
    }
    
    
    public function testWithVariedCurlCodesCodes() {
        $this->loadHttpClientFixtures(array(
            new ConnectException(
                'cURL error 6: Couldn\'t resolve host. The given remote host was not resolved.',
                new Request('GET', 'http://example.com/')
            ),
            new ConnectException(
                'cURL error 28: Operation timeout. The specified time-out period was reached according to the conditions.',
                new Request('GET', 'http://example.com/')
            ),
            new ConnectException(
                'cURL error 55: Failed sending network data.',
                new Request('GET', 'http://example.com/')
            ),
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok'
        ));

        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example01', 'http://example.com'));        
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(
            new LinkResult('http://www.youtube.com/example', '<a href="http://www.youtube.com/example"><img src="/images/youtube.png"></a>', new LinkState(LinkState::TYPE_CURL, 6)),
            new LinkResult('http://example.com/images/youtube.png', '<img src="/images/youtube.png">', new LinkState(LinkState::TYPE_CURL, 28)),
            new LinkResult('http://blog.example.com/', '<a href="http://blog.example.com"><img src="/images/blog.png"></a>', new LinkState(LinkState::TYPE_CURL, 55)),
        ), $checker->getErrored());    
    }   
    
    
    public function testWithMixedHttpStatusCodesAndCurlCodes() {
        $this->loadHttpClientFixtures(array(
            new ConnectException(
                'cURL error 6: Couldn\'t resolve host. The given remote host was not resolved.',
                new Request('GET', 'http://example.com/')
            ),
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 500 Internal Server Error',          
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok'
        ));      
        
        $webPage = new WebPage();
        $webPage->setHttpResponse($this->getHttpFixtureFromHtmlDocument('example01', 'http://example.com'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
      
        $this->assertEquals(array(
            new LinkResult('http://www.youtube.com/example', '<a href="http://www.youtube.com/example"><img src="/images/youtube.png"></a>', new LinkState(LinkState::TYPE_CURL, 6)),
            new LinkResult('http://blog.example.com/', '<a href="http://blog.example.com"><img src="/images/blog.png"></a>', new LinkState(LinkState::TYPE_HTTP, 500))
        ), $checker->getErrored());
    }
}