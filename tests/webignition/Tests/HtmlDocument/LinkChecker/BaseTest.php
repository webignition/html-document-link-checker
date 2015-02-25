<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\HtmlDocument\LinkChecker\LinkChecker;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Message\ResponseInterface as HttpResponse;
use GuzzleHttp\Subscriber\History as HttpHistorySubscriber;
use GuzzleHttp\Subscriber\Mock as HttpMockSubscriber;
use GuzzleHttp\Message\MessageFactory as HttpMessageFactory;

abstract class BaseTest extends \PHPUnit_Framework_TestCase {
    
    /**
     *
     * @var HttpClient
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
     * @return HttpResponse
     */
    protected function getHttpFixtureFromHtmlDocument($name, $effectiveUrl) {
        $response = $this->getHttpResponseFromMessage("HTTP/1.0 200 OK\nContent-Type:text/html\n\n" . $this->getHtmlDocumentFixture($name));
        $response->setEffectiveUrl($effectiveUrl);
        
        return $response;      
    }
    
    
    /**
     * 
     * @return HttpClient
     */
    protected function getHttpClient() {
        if (is_null($this->httpClient)) {
            $this->httpClient = new HttpClient();
            $this->httpClient->getEmitter()->attach(new HttpHistorySubscriber());
        }
        
        return $this->httpClient;
    }
    
    
    /**
     * 
     * @return HttpHistorySubscriber
     */
    protected function getHttpHistory() {
        $listenerCollections = $this->getHttpClient()->getEmitter()->listeners('complete');

        foreach ($listenerCollections as $listener) {
            if ($listener[0] instanceof HttpHistorySubscriber) {
                return $listener[0];
            }
        }
        
        return null;     
    }     


    /**
     * @param array $items
     */
    protected function loadHttpClientFixtures($items) {
        $this->getHttpClient()->getEmitter()->attach(
            new HttpMockSubscriber(
                $items
            )
        );
    }
    
    
    
    /**
     * 
     * @return \webignition\HtmlDocument\LinkChecker\LinkChecker
     */
    protected function getDefaultChecker() {
        $checker = new LinkChecker();
        $checker->getConfiguration()->setHttpClient($this->getHttpClient());
        $checker->getUrlHealthChecker()->getConfiguration()->setBaseRequest($this->getHttpClient()->createRequest('GET'));
        $checker->getUrlHealthChecker()->getConfiguration()->disableRetryOnBadResponse();
        $checker->getUrlHealthChecker()->getConfiguration()->setHttpMethodList(array('GET'));
        
        return $checker;
    }


    /**
     * @param $message
     * @return HttpResponse
     */
    protected function getHttpResponseFromMessage($message) {
        $factory = new HttpMessageFactory();
        return $factory->fromMessage($message);
    }
    
}