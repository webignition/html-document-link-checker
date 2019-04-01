<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\HtmlDocument\LinkChecker\Configuration;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param array $configurationValues
     * @param bool $expectedIgnoreFragmentInUrlComparison
     */
    public function testCreate(
        array $configurationValues,
        $expectedIgnoreFragmentInUrlComparison
    ) {
        $configuration = new Configuration($configurationValues);

        $this->assertEquals($expectedIgnoreFragmentInUrlComparison, $configuration->getIgnoreFragmentInUrlComparison());
    }

    public function createDataProvider(): array
    {
        return [
            'defaults' => [
                'configurationValues' => [],
                'expectedIgnoreFragmentInUrlComparison' => false,
            ],
            'ignore fragment in url comparison' => [
                'configurationValues' => [
                    Configuration::KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON => true,
                ],
                'expectedIgnoreFragmentInUrlComparison' => true,
            ],
        ];
    }
}
