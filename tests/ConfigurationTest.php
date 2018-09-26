<?php

namespace webignition\Tests\HtmlDocument\LinkChecker;

use webignition\HtmlDocument\LinkChecker\Configuration;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param array $configurationValues
     * @param array $expectedSchemesToExclude
     * @param array $expectedUrlsToExclude
     * @param array $expectedDomainsToExclude
     * @param bool $expectedIgnoreFragmentInUrlComparison
     */
    public function testCreate(
        array $configurationValues,
        array $expectedSchemesToExclude,
        array $expectedUrlsToExclude,
        array $expectedDomainsToExclude,
        $expectedIgnoreFragmentInUrlComparison
    ) {
        $configuration = new Configuration($configurationValues);

        $this->assertEquals($expectedSchemesToExclude, $configuration->getSchemesToExclude());
        $this->assertEquals($expectedUrlsToExclude, $configuration->getUrlsToExclude());
        $this->assertEquals($expectedDomainsToExclude, $configuration->getDomainsToExclude());
        $this->assertEquals($expectedIgnoreFragmentInUrlComparison, $configuration->getIgnoreFragmentInUrlComparison());
    }

    public function createDataProvider(): array
    {
        $defaultSchemesToExclude = [
            Configuration::URL_SCHEME_MAILTO,
            Configuration::URL_SCHEME_ABOUT,
            Configuration::URL_SCHEME_JAVASCRIPT,
            Configuration::URL_SCHEME_FTP,
            Configuration::URL_SCHEME_TEL
        ];

        return [
            'defaults' => [
                'configurationValues' => [],
                'expectedSchemesToExclude' => $defaultSchemesToExclude,
                'expectedUrlsToExclude' => [],
                'expectedDomainsToExclude' => [],
                'expectedIgnoreFragmentInUrlComparison' => false,
            ],
            'set schemes to exclude' => [
                'configurationValues' => [
                    Configuration::KEY_SCHEMES_TO_EXCLUDE => [
                        'foo',
                        'bar',
                    ],
                ],
                'expectedSchemesToExclude' => [
                    'foo',
                    'bar',
                ],
                'expectedUrlsToExclude' => [],
                'expectedDomainsToExclude' => [],
                'expectedIgnoreFragmentInUrlComparison' => false,
            ],
            'set urls to exclude' => [
                'configurationValues' => [
                    Configuration::KEY_URLS_TO_EXCLUDE => [
                        'http://foo.example.com/bar',
                    ],
                ],
                'expectedSchemesToExclude' => $defaultSchemesToExclude,
                'expectedUrlsToExclude' => [
                    'http://foo.example.com/bar',
                ],
                'expectedDomainsToExclude' => [],
                'expectedIgnoreFragmentInUrlComparison' => false,
            ],
            'set domains to exclude' => [
                'configurationValues' => [
                    Configuration::KEY_DOMAINS_TO_EXCLUDE => [
                        'example.com',
                    ],
                ],
                'expectedSchemesToExclude' => $defaultSchemesToExclude,
                'expectedUrlsToExclude' => [],
                'expectedDomainsToExclude' => [
                    'example.com',
                ],
                'expectedIgnoreFragmentInUrlComparison' => false,
            ],
            'ignore fragment in url comparison' => [
                'configurationValues' => [
                    Configuration::KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON => true,
                ],
                'expectedSchemesToExclude' => $defaultSchemesToExclude,
                'expectedUrlsToExclude' => [],
                'expectedDomainsToExclude' => [],
                'expectedIgnoreFragmentInUrlComparison' => true,
            ],
        ];
    }
}
