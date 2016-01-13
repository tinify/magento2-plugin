<?php

namespace Tinify\Magento;

use AspectMock;
use Tinify;

class ConfigIntegrationTest extends \Tinify\Magento\IntegrationTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadArea("adminhtml");

        $this->config = $this->getObjectManager()->get(
            "Tinify\Magento\Model\Config"
        );

        $this->messageList = $this->getObjectManager()->get(
            "Magento\Framework\Notification\MessageList"
        );
    }

    public function testMessageListContainsVisibleMessageIfKeyIsUnset()
    {
        $hash = md5("Tinify\Magento\Model\Message\Unconfigured");
        $this->assertEquals(
            true,
            $this->messageList->getMessageByIdentity($hash)->isDisplayed()
        );
    }

    public function testMessageListContainsInvisibleMessageIfKeyIsSet()
    {
        $scopeConfig = $this->getObjectManager()->get(
            "Magento\Framework\App\Config\MutableScopeConfigInterface"
        );
        $scopeConfig->setValue(Model\Config::KEY_PATH, "my_api_key");

        $hash = md5("Tinify\Magento\Model\Message\Unconfigured");
        $this->assertEquals(
            false,
            $this->messageList->getMessageByIdentity($hash)->isDisplayed()
        );
    }

    public function testMessageListContainsLinkToConfiguration()
    {
        $scopeConfig = $this->getObjectManager()->get(
            "Magento\Framework\App\Config\MutableScopeConfigInterface"
        );
        $scopeConfig->setValue(Model\Config::KEY_PATH, "my_api_key");

        $hash = md5("Tinify\Magento\Model\Message\Unconfigured");
        $this->assertContains(
            "http://localhost:3000/admin/system_config/edit/section/tinify_compress_images/key",
            $this->messageList->getMessageByIdentity($hash)->getText()->render()
        );
    }
}
