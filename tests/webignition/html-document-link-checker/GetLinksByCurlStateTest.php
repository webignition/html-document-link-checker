<?php

namespace webignition\HtmlDocumentLinkChecker\Tests;

use webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker;
use webignition\HtmlDocumentLinkChecker\LinkState;
use webignition\WebResource\WebPage\WebPage;

class GetLinksByCurlStateTest extends BaseTest {
    
    public function testWithNoWebPage() {
        $checker = new HtmlDocumentLinkChecker();        
        $this->assertEquals(array(), $checker->getByCurlState(6));     
        $this->assertEquals(array(), $checker->getByCurlState(28));     
        $this->assertEquals(array(), $checker->getByCurlState(55));     
    }
    
    public function testWithNoLinks() {
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example03'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(), $checker->getByCurlState(6));     
        $this->assertEquals(array(), $checker->getByCurlState(28));     
        $this->assertEquals(array(), $checker->getByCurlState(55));
    }    
    
    public function testWithVariedCodes() {
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
            $curl6Exception,
            $curl6Exception,
            $curl6Exception,
            $curl6Exception,
            $curl6Exception
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example01'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        
        $this->assertEquals(array(
            new LinkState('curl', 6, 'http://example.com/relative-path', '<a href="relative-path">Relative Path</a>'),
            new LinkState('curl', 6, 'http://example.com/#fragment-only', '<a href="#fragment-only">Fragment Only</a>'),
            new LinkState('curl', 6, 'http://example.com/images/youtube.png', '<img src="/images/youtube.png">'),
            new LinkState('curl', 6, 'http://blog.example.com/', '<a href="http://blog.example.com"><img src="/images/blog.png"></a>'),
            new LinkState('curl', 6, 'http://example.com/images/blog.png', '<img src="/images/blog.png">'),
            new LinkState('curl', 6, 'http://twitter.com/example', '<a href="http://twitter.com/example"><img src="/images/twitter.png"></a>'),
            new LinkState('curl', 6, 'http://example.com/images/twitter.png', '<img src="/images/twitter.png">'),
        ), $checker->getByCurlState(6)); 
        
        $this->assertEquals(array(
            new LinkState('curl', 28, 'http://example.com/root-relative-path', '<a href="/root-relative-path">Root Relative Path</a>'),
            new LinkState('curl', 28, 'http://example.com/protocol-relative-same-host', '<a href="//example.com/protocol-relative-same-host">Protocol Relative Same Host</a>'),
        ), $checker->getByCurlState(28));        
        
        
        $this->assertEquals(array(
            new LinkState('curl', 55, 'http://another.example.com/protocol-relative-same-host', '<a href="//another.example.com/protocol-relative-same-host">Protocol Relative Different Host</a>'),
            new LinkState('curl', 55, 'http://example.com/#fragment-only', '<a href="#fragment-only">Repeated Fragment Only (should be ignored)</a>'),
            new LinkState('curl', 55, 'http://www.youtube.com/example', '<a href="http://www.youtube.com/example"><img src="/images/youtube.png"></a>'),            
        ), $checker->getByCurlState(55));   
    }    
    
    
}