<?php
namespace webignition\HtmlDocument\LinkChecker;

use webignition\UrlHealthChecker\LinkState;

class LinkResult
{
    /**
     * @var string
     */
    private $context =  null;

    /**
     * @var string
     */
    private $url = null;

    /**
     * @var LinkState
     */
    private $linkState = null;

    public function __construct($url, $context, LinkState $linkState)
    {
        $this->setUrl($url);
        $this->setContext($context);
        $this->setLinkState($linkState);
    }

    public function setLinkState(LinkState $linkState)
    {
        $this->linkState = $linkState;
    }

    public function getLinkState(): LinkState
    {
        return $this->linkState;
    }

    public function setContext(string $context)
    {
        $this->context = $context;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
