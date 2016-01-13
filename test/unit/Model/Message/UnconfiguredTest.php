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

        $this->message = $this->getObjectManager()->getObject(
            "Tinify\Magento\Model\Message\Unconfigured",
            [
                "config" => $this->config,
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
            ->method("getKey")
            ->willReturn("");

        $this->assertTrue($this->message->isDisplayed());
    }

    public function testIsDisplayedReturnsFalseIfKeyIsSet()
    {
        $this->config
            ->method("getKey")
            ->willReturn("this_is_my_key");

        $this->assertFalse($this->message->isDisplayed());
    }
}
