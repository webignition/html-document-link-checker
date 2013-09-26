<?php

namespace webignition\HtmlDocumentLinkChecker\Tests;

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
        return file_get_contents(__DIR__ . '/../../fixtures/html-documents/' . $name . '.html');
    }
    
    
    /**
     * 
     * @return \Guzzle\Http\Client
     */
    protected function getHttpClient() {
        if (is_null($this->httpClient)) {
            $this->httpClient = new \Guzzle\Http\Client();
        }
        
        return $this->httpClient;
    }
    
    
    /**
     * 
     * @param array $httpMessages
     */
    protected function loadHttpFixtures($httpMessages) {
        $plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        
        foreach ($httpMessages as $httpMessage) {            
            $plugin->addResponse(\Guzzle\Http\Message\Response::fromMessage($httpMessage));
        }
        
        $this->getHttpClient()->addSubscriber($plugin);
    }
    
}