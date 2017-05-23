<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use PHPUnit_Framework_TestCase;
use webignition\HtmlDocument\LinkChecker\Configuration;

class ConfigurationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    protected function setUp()
    {
        parent::setUp();

        $this->configuration = new Configuration();
    }

    public function testIgnoreFragmentInUrlComparisonIsDisabledByDefault()
    {
        $this->assertFalse($this->configuration->ignoreFragmentInUrlComparison());
    }

    public function testDisableIgnoreFragmentInUrlComparison()
    {
        $this->configuration->disableIgnoreFragmentInUrlComparison();
        $this->assertFalse($this->configuration->ignoreFragmentInUrlComparison());
    }

    public function testEnableIgnoreFragmentInUrlComparison()
    {
        $this->configuration->enableIgnoreFragmentInUrlComparison();
        $this->assertTrue($this->configuration->ignoreFragmentInUrlComparison());
    }

    /**
     * @dataProvider valuesToExcludeDataProvider
     *
     * @param string[] $valuesToExclude
     * @param string[] $expectedValuesToExclude
     */
    public function testSetDomainsToExclude($valuesToExclude, $expectedValuesToExclude)
    {
        $this->assertEmpty($this->configuration->getDomainsToExclude());
        $this->configuration->setDomainsToExclude($valuesToExclude);
        $this->assertEquals($expectedValuesToExclude, $this->configuration->getDomainsToExclude());
    }

    public function testGetDefaultSchemesToExclude()
    {
        $this->assertEquals(
            [
                Configuration::URL_SCHEME_MAILTO,
                Configuration::URL_SCHEME_ABOUT,
                Configuration::URL_SCHEME_JAVASCRIPT,
                Configuration::URL_SCHEME_FTP,
                Configuration::URL_SCHEME_TEL,
            ],
            $this->configuration->getSchemesToExclude()
        );
    }

    /**
     * @dataProvider valuesToExcludeDataProvider
     *
     * @param string[] $valuesToExclude
     * @param string[] $expectedValuesToExclude
     */
    public function testSetSchemesToExclude($valuesToExclude, $expectedValuesToExclude)
    {
        $this->configuration->setSchemesToExclude($valuesToExclude);
        $this->assertEquals($expectedValuesToExclude, $this->configuration->getSchemesToExclude());
    }

    /**
     * @dataProvider valuesToExcludeDataProvider
     *
     * @param string[] $valuesToExclude
     * @param string[] $expectedValuesToExclude
     */
    public function testSetUrlsToExclude($valuesToExclude, $expectedValuesToExclude)
    {
        $this->configuration->setSchemesToExclude($valuesToExclude);
        $this->assertEquals($expectedValuesToExclude, $this->configuration->getSchemesToExclude());
    }

    /**
     * @return array
     */
    public function valuesToExcludeDataProvider()
    {
        return [
            'none' => [
                'valuesToExclude' => [],
                'expectedValuesToExclude' => [],
            ],
            'one' => [
                'valuesToExclude' => [
                    'foo',
                ],
                'expectedValuesToExclude' => [
                    'foo',
                ],
            ],
            'many' => [
                'valuesToExclude' => [
                    'foo',
                    'bar',
                    'foobar',
                ],
                'expectedValuesToExclude' => [
                    'foo',
                    'bar',
                    'foobar',
                ],
            ],
        ];
    }
}
