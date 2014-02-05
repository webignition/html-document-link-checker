<?php

namespace webignition\Tests\HtmlDocument\LinkChecker\Configuration;

use webignition\Tests\HtmlDocument\LinkChecker\BaseTest;
use webignition\HtmlDocument\LinkChecker\Configuration;

abstract class ConfigurationTest extends BaseTest {
    
    /**
     *
     * @var \webignition\HtmlDocument\LinkChecker\Configuration
     */
    private $configuration;
    
    
    public function setUp() {
        $this->configuration = new Configuration();
    }  
    
    
    /**
     * 
     * @return \webignition\HtmlDocument\LinkChecker\Configuration
     */
    protected function getConfiguration() {
        return $this->configuration;
    }
}