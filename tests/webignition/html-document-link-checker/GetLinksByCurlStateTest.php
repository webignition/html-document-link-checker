<?php

namespace webignition\HtmlDocumentLinkChecker\Tests;

use webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker;
use webignition\WebResource\WebPage\WebPage;

class GetLinksByCurlStateTest extends BaseTest {
    
    public function testWithNoWebPage() {
        $checker = new HtmlDocumentLinkChecker();        
        $this->assertEquals(array(), $checker->getLinksByCurlState(6));     
        $this->assertEquals(array(), $checker->getLinksByCurlState(28));     
        $this->assertEquals(array(), $checker->getLinksByCurlState(55));     
    }
    
    public function testWithNoLinks() {
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example03'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        
        $this->assertEquals(array(), $checker->getLinksByCurlState(6));     
        $this->assertEquals(array(), $checker->getLinksByCurlState(28));     
        $this->assertEquals(array(), $checker->getLinksByCurlState(55));
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
            $curl6Exception
        ));
        
        $webPage = new WebPage();
        $webPage->setUrl('http://example.com/');
        $webPage->setContent($this->getHtmlDocumentFixture('example01'));
        
        $checker = new HtmlDocumentLinkChecker();
        $checker->setWebPage($webPage);
        $checker->setHttpClient($this->getHttpClient());
        
        $this->assertEquals(array(
            'http://example.com/relative-path',
            'http://example.com/#fragment-only',
            'http://twitter.com/example'
        ), $checker->getLinksByCurlState(6)); 
        
        $this->assertEquals(array(
            'http://example.com/root-relative-path',
            'http://example.com/protocol-relative-same-host'
        ), $checker->getLinksByCurlState(28)); 
        
        $this->assertEquals(array(
            'http://another.example.com/protocol-relative-same-host',
            'http://www.youtube.com/example',
            'http://blog.example.com/'
        ), $checker->getLinksByCurlState(55));     
    }    
    
    
}