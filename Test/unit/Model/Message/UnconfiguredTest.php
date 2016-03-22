<?php

namespace Tinify\Magento\Model\Message;

class UnconfiguredTest extends \Tinify\Magento\TestCase
{
    protected function setUp()
    {
        $this->config = $this
            ->getMockBuilder("Tinify\Magento\Model\Config")
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilder = $this
            ->getMockBuilder("Magento\Framework\UrlInterface")
            ->disableOriginalConstructor()
            ->getMock();

        $this->message = $this->getObjectManager()->getObject(
            "Tinify\Magento\Model\Message\Unconfigured",
            [
                "config" => $this->config,
                "urlBuilder" => $this->urlBuilder,
            ]
        );
    }

    public function testGetIdentityReturnsClassNameHash()
    {
        $hash = md5("Tinify\Magento\Model\Message\Unconfigured");
        $this->assertEquals($hash, $this->message->getIdentity());
    }

    public function testIsDisplayedReturnsTrueIfKeyIsUnset()
    {
        $this->config
            ->method("hasKey")
            ->willReturn(false);

        $this->assertTrue($this->message->isDisplayed());
    }

    public function testIsDisplayedReturnsFalseIfKeyIsSet()
    {
        $this->config
            ->method("hasKey")
            ->willReturn(true);

        $this->assertFalse($this->message->isDisplayed());
    }

    public function testGetTextReturnsTextWithGeneratedUrl()
    {
        $this->urlBuilder
            ->expects($this->once())
            ->method("getUrl")
            ->with("adminhtml/system_config/edit", ["section" => "tinify_compress_images"])
            ->willReturn("http://localhost/my_admin/my_awesome_url");

        $this->assertContains(
            "http://localhost/my_admin/my_awesome_url",
            $this->message->getText()->__toString()
        );
    }

    public function testGetSeverityReturnsMajor()
    {
        $this->assertEquals(2, $this->message->getSeverity());
    }
}
