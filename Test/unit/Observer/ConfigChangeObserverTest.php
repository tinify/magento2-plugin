<?php

namespace Tinify\Magento\Model;

class ConfigChangeObserverTest extends \Tinify\Magento\TestCase
{
    protected function setUp()
    {
        $this->status = $this
            ->getMockBuilder("Tinify\Magento\Model\Config\ConnectionStatus")
            ->disableOriginalConstructor()
            ->getMock();

        $this->changeObserver = $this->getObjectManager()->getObject(
            "Tinify\Magento\Observer\ConfigChangeObserver",
            [
                "status" => $this->status,
            ]
        );

        $this->observer = $this->getMock(
            "Magento\Framework\Event\Observer"
        );
    }

    public function testExecuteValidatesConfiguration()
    {
        $this->status
            ->expects($this->once())
            ->method("update");

        $this->changeObserver->execute($this->observer);
    }
}
