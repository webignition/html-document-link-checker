<?php

namespace webignition\Tests\HtmlDocumentLinkChecker;

use webignition\HtmlDocumentLinkChecker\LinkCheckResult;
use webignition\HtmlDocumentLinkChecker\LinkState;
use webignition\WebResource\WebPage\WebPage;

class GetErroredLinksTest extends BaseTest {   

    
    public function testWithNoWebPage() {     
        $this->assertEquals(array(), $this->getDefaultChecker()->getErrored());         
    }
    
    public function testWithNoLinks() {
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example03'));
        
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
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example01'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);      
        
        $this->assertEquals(array(
            new LinkCheckResult('http://example.com/images/youtube.png', '<img src="/images/youtube.png">', new LinkState(LinkState::TYPE_HTTP, 404)),
            new LinkCheckResult('http://blog.example.com/', '<a href="http://blog.example.com"><img src="/images/blog.png"></a>', new LinkState(LinkState::TYPE_HTTP, 500)),
            new LinkCheckResult('http://example.com/images/blog.png', '<img src="/images/blog.png">', new LinkState(LinkState::TYPE_HTTP, 410)),
        ), $checker->getErrored());
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
            $curl55Exception,
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok'
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example01'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);       
        
        $this->assertEquals(array(
            new LinkCheckResult('http://www.youtube.com/example', '<a href="http://www.youtube.com/example"><img src="/images/youtube.png"></a>', new LinkState(LinkState::TYPE_CURL, 6)),
            new LinkCheckResult('http://example.com/images/youtube.png', '<img src="/images/youtube.png">', new LinkState(LinkState::TYPE_CURL, 28)),
            new LinkCheckResult('http://blog.example.com/', '<a href="http://blog.example.com"><img src="/images/blog.png"></a>', new LinkState(LinkState::TYPE_CURL, 55)),
        ), $checker->getErrored());    
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
            'HTTP/1.1 500 Internal Server Error',          
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok'
        ));      
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example01'));
        
        $checker = $this->getDefaultChecker();
        $checker->setWebPage($webPage);
      
        $this->assertEquals(array(
            new LinkCheckResult('http://www.youtube.com/example', '<a href="http://www.youtube.com/example"><img src="/images/youtube.png"></a>', new LinkState(LinkState::TYPE_CURL, 6)),
            new LinkCheckResult('http://blog.example.com/', '<a href="http://blog.example.com"><img src="/images/blog.png"></a>', new LinkState(LinkState::TYPE_HTTP, 500))
        ), $checker->getErrored());
    }
}