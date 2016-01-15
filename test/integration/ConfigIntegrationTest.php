<?php

namespace Tinify\Magento;

use AspectMock;
use Tinify;
use Tinify\Magento\Model\Config\ConnectionStatus;

class ConfigIntegrationTest extends \Tinify\Magento\IntegrationTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->useFilesystemRoot();
        $this->loadArea("adminhtml");

        $this->config = $this->getObjectManager()->get(
            "Tinify\Magento\Model\Config"
        );

        $this->messageList = $this->getObjectManager()->get(
            "Magento\Framework\Notification\MessageList"
        );

        $this->configModel = $this->getObjectManager()->get(
            "Magento\Config\Model\Config"
        );

        AspectMock\Test::double("Tinify\Client", [
            "request" => function () {
                throw new Tinify\ClientException("error");
            }
        ]);

        Tinify\Tinify::setCompressionCount(6);
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
        $this->configModel->setDataByPath(Model\Config::KEY_PATH, "my_api_key");
        $this->configModel->save();

        $hash = md5("Tinify\Magento\Model\Message\Unconfigured");
        $this->assertEquals(
            false,
            $this->messageList->getMessageByIdentity($hash)->isDisplayed()
        );
    }

    public function testMessageListContainsLinkToConfiguration()
    {
        $this->configModel->setDataByPath(Model\Config::KEY_PATH, "my_api_key");
        $this->configModel->save();

        $hash = md5("Tinify\Magento\Model\Message\Unconfigured");
        $this->assertContains(
            "http://localhost:3000/admin/system_config/edit/section/tinify_compress_images/key",
            $this->messageList->getMessageByIdentity($hash)->getText()->render()
        );
    }

    public function testConnectionStatusIsUpdatedWithSuccessAfterConfigSave()
    {
        $this->configModel->setDataByPath(Model\Config::KEY_PATH, "my_new_key");
        $this->configModel->save();

        $label = $this->getObjectManager()->get(
            "Magento\Framework\Data\Form\Element\Label"
        );

        $status = $this->getObjectManager()->get(
            "Tinify\Magento\Model\Config\ConnectionStatusField"
        );

        $this->assertEquals(
            '<td class="value">' .
                '<div class="tinify-connection-status tinify-success">' .
                    '<div class="control-value">API connection successful.</div>' .
                '</div>' .
                '<p class="note"><span>You have made 6 compressions this month.</span></p>' .
            '</td>',
            $this->callMethod($status, "_renderValue", $label)
        );
    }

    public function testConnectionStatusIsUpdatedWithFailureAfterConfigSave()
    {
        AspectMock\Test::double("Tinify\Client", [
            "request" => function () {
                throw new Tinify\AccountException("help an error");
            }
        ]);

        $this->configModel->setDataByPath(Model\Config::KEY_PATH, "my_new_key");
        $this->configModel->save();

        $label = $this->getObjectManager()->get(
            "Magento\Framework\Data\Form\Element\Label"
        );

        $status = $this->getObjectManager()->get(
            "Tinify\Magento\Model\Config\ConnectionStatusField"
        );

        $this->assertEquals(
            '<td class="value">' .
                '<div class="tinify-connection-status tinify-failure">' .
                    '<div class="control-value">API connection unsuccessful.</div>' .
                '</div>' .
                '<p class="note"><span>Last error: help an error</span></p>' .
            '</td>',
            $this->callMethod($status, "_renderValue", $label)
        );
    }
}
