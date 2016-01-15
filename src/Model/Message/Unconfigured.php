<?php

namespace Tinify\Magento\Model\Message;

use Tinify\Magento\Model\Config;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

class Unconfigured implements MessageInterface
{
    protected $config;
    protected $urlBuilder;

    public function __construct(Config $config, UrlInterface $urlBuilder)
    {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
    }

    public function isDisplayed()
    {
        return !$this->config->hasKey();
    }

    public function getIdentity()
    {
        return md5(get_class($this));
    }

    public function getText()
    {
        $url = $this->urlBuilder->getUrl("adminhtml/system_config/edit", [
            "section" => explode("/", Config::KEY_PATH)[0]
        ]);
        return __("Configure <a href=\"%1\">your TinyPNG API key</a> to start compressing images.", $url);
    }

    public function getSeverity()
    {
        return MessageInterface::SEVERITY_MAJOR;
    }
}
