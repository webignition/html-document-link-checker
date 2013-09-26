<?php

namespace webignition\HtmlDocumentLinkChecker\Tests;

use webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker;
use webignition\HtmlDocumentLinkChecker\LinkState;
use webignition\WebResource\WebPage\WebPage;

class GetErroredLinksTest extends BaseTest {
    
    public function testWithNoWebPage() {
        $checker = new HtmlDocumentLinkChecker();        
        $this->assertEquals(array(), $checker->getErroredLinks());         
    }
    
    public function testWithNoLinks() {
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example03'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(), $checker->getErroredLinks());  
    }    
    
    public function testWithVariedHttpStatusCodes() {
        $this->loadHttpClientFixtures(array(
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 500 Internal Server Error',
            'HTTP/1.1 410 Gone',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 200 Ok',
            'HTTP/1.1 404 Not Found',
            'HTTP/1.1 400 Bad Request'
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example01'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        
        $this->assertEquals(array(
            'http://example.com/root-relative-path' => new LinkState('http', 404),
            'http://example.com/protocol-relative-same-host' => new LinkState('http', 500),
            'http://another.example.com/protocol-relative-same-host' => new LinkState('http', 410),
            'http://blog.example.com/' => new LinkState('http', 404),
            'http://twitter.com/example' => new LinkState('http', 400),
        ), $checker->getErroredLinks());      
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
            $curl55Exception,
            $curl55Exception,
            $curl6Exception
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example01'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        
        $this->assertEquals(array(
            'http://example.com/relative-path' => new LinkState('curl', 6),
            'http://example.com/root-relative-path' => new LinkState('curl', 28),
            'http://example.com/protocol-relative-same-host' => new LinkState('curl', 28),
            'http://another.example.com/protocol-relative-same-host' => new LinkState('curl', 55),
            'http://example.com/#fragment-only' => new LinkState('curl', 6),
            'http://www.youtube.com/example' => new LinkState('curl', 55),
            'http://blog.example.com/' => new LinkState('curl', 55),
            'http://twitter.com/example' => new LinkState('curl', 6),
        ), $checker->getErroredLinks());    
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
            'HTTP/1.1 404 Not Found', 
            $curl55Exception,
            'HTTP/1.1 200 Ok',
        ));      
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example01'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        
        $this->assertEquals(array(
            'http://example.com/relative-path' => new LinkState('curl', 6),
            'http://example.com/protocol-relative-same-host' => new LinkState('curl', 28),
            'http://another.example.com/protocol-relative-same-host' => new LinkState('http', 500),
            'http://example.com/#fragment-only' => new LinkState('http', 400),
            'http://www.youtube.com/example' => new LinkState('http', 404),
            'http://blog.example.com/' => new LinkState('curl', 55)
        ), $checker->getErroredLinks());        
    }    
    
    
}