<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\HtmlDocument\LinkChecker\LinkChecker;

abstract class BaseTest extends \PHPUnit_Framework_TestCase {
    
    /**
     *
     * @var \Guzzle\Http\Client
     */
    private $httpClient;
    
    /**
     * 
     * @param string $name
     * @return string
     */
    protected function getHtmlDocumentFixture($name) {
        return file_get_contents(__DIR__ . '/../../../../fixtures/html-documents/' . $name . '.html');
    }
    
    
    /**
     * 
     * @param string $name
     * @param string $effectiveUrl
     * @return \Guzzle\Http\Message\Response
     */
    protected function getHttpFixtureFromHtmlDocument($name, $effectiveUrl) {
        $response = \Guzzle\Http\Message\Response::fromMessage("HTTP/1.0 200 OK\nContent-Type:text/html\n\n" . $this->getHtmlDocumentFixture($name));
        $response->setEffectiveUrl($effectiveUrl);
        
        return $response;      
    }
    
    
    /**
     * 
     * @return \Guzzle\Http\Client
     */
    protected function getHttpClient() {
        if (is_null($this->httpClient)) {
            $this->httpClient = new \Guzzle\Http\Client();
            $this->httpClient->addSubscriber(new \Guzzle\Plugin\History\HistoryPlugin());
        }
        
        return $this->httpClient;
    }
    
    
    /**
     * 
     * @return \Guzzle\Plugin\History\HistoryPlugin|null
     */
    protected function getHttpHistory() {
        $listenerCollections = $this->getHttpClient()->getEventDispatcher()->getListeners('request.sent');
        
        foreach ($listenerCollections as $listener) {
            if ($listener[0] instanceof \Guzzle\Plugin\History\HistoryPlugin) {
                return $listener[0];
            }
        }
        
        return null;     
    }     


    /**
     * @param array $items
     */
    protected function loadHttpClientFixtures($items) {
        $plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        
        foreach ($items as $item) {
            if ($item instanceof \Exception) {
                $plugin->addException($item);
            } elseif (is_string($item)) {
                $plugin->addResponse(\Guzzle\Http\Message\Response::fromMessage($item));
            }
        }
        
        $this->getHttpClient()->addSubscriber($plugin);
    }
    
    
    
    /**
     * 
     * @return \webignition\HtmlDocument\LinkChecker\LinkChecker
     */
    protected function getDefaultChecker() {
        $checker = new LinkChecker();
        $checker->getUrlHealthChecker()->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        $checker->getUrlHealthChecker()->getConfiguration()->disableRetryOnBadResponse();
        $checker->getUrlHealthChecker()->getConfiguration()->setHttpMethodList(array('GET'));
        
        return $checker;
    }
    
}