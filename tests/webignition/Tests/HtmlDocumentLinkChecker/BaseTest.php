<?php

namespace webignition\Tests\HtmlDocumentLinkChecker;

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
        return file_get_contents(__DIR__ . '/../../../fixtures/html-documents/' . $name . '.html');
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
     * @param array $httpMessages
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
    
}