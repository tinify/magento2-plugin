<?php

namespace Tinify\Magento\Model\Config;

class ConnectionStatusFieldTest extends \Tinify\Magento\TestCase
{
    protected function setUp()
    {
        $this->status = $this
            ->getMockBuilder("Tinify\Magento\Model\Config\ConnectionStatus")
            ->disableOriginalConstructor()
            ->getMock();

        $this->statusField = $this->getObjectManager()->getObject(
            "Tinify\Magento\Model\Config\ConnectionStatusField",
            [
                "status" => $this->status,
            ]
        );

        $this->label = $this->getObjectManager()->getObject(
            "Magento\Framework\Data\Form\Element\Label"
        );
    }

    public function testGetElementHtmlReturnsUnknownStatus()
    {
        $this->status
            ->method("getStatus")
            ->willReturn(ConnectionStatus::UNKNOWN);

        $this->assertContains(
            "Save configuration to check status",
            $this->statusField->getElementHtml($this->label)
        );

        $this->assertEquals("", $this->label->getComment());
    }

    public function testGetElementHtmlReturnsSuccessfulStatus()
    {
        $this->status
            ->method("getStatus")
            ->willReturn(ConnectionStatus::SUCCESS);

        $this->status
            ->method("getCompressionCount")
            ->willReturn(10);

        $this->assertEquals(
            '<div class="tinify-connection-status tinify-success">' .
                '<div class="control-value">API connection successful.</div>' .
            '</div>',
            $this->statusField->getElementHtml($this->label)
        );

        $this->assertEquals(
            "You have made 10 compressions this month.",
            $this->label->getComment()
        );
    }

    public function testGetElementHtmlReturnsUnsuccessfulStatus()
    {
        $this->status
            ->method("getStatus")
            ->willReturn(ConnectionStatus::FAILURE);

        $this->status
            ->method("getLastError")
            ->willReturn("Credentials are invalid (HTTP 401/Unauthorized)");

        $this->assertEquals(
            '<div class="tinify-connection-status tinify-failure">' .
                '<div class="control-value">API connection unsuccessful.</div>' .
            '</div>',
            $this->statusField->getElementHtml($this->label)
        );

        $this->assertEquals(
            "Error: Credentials are invalid (HTTP 401/Unauthorized)",
            $this->label->getComment()
        );
    }
}
