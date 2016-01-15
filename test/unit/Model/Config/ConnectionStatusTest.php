<?php

namespace Tinify\Magento\Model\Config;

use AspectMock;
use Tinify;

class ConnectionStatusTest extends \Tinify\Magento\TestCase
{
    protected function setUp()
    {
        $this->config = $this
            ->getMockBuilder("Tinify\Magento\Model\Config")
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = $this
            ->getMockBuilder("Magento\Framework\App\CacheInterface")
            ->disableOriginalConstructor()
            ->getMock();

        $this->status = $this->getObjectManager()->getObject(
            "Tinify\Magento\Model\Config\ConnectionStatus",
            [
                "config" => $this->config,
                "cache" => $this->cache,
            ]
        );

        Tinify\setKey(null);
        Tinify\Tinify::setCompressionCount(null);
    }

    public function testGetStatusReturnsUnknownByDefault()
    {
        $this->assertEquals(ConnectionStatus::UNKNOWN, $this->status->getStatus());
    }

    public function testGetStatusReturnsAssignedStatus()
    {
        $this->callMethod($this->status, "setStatus", ConnectionStatus::FAILURE);
        $this->assertEquals(ConnectionStatus::FAILURE, $this->status->getStatus());
    }

    public function testGetStatusReturnsDeserializedStatus()
    {
        $this->cache
            ->method("load")
            ->willReturn(serialize(["status" => 1]));

        $this->callMethod($this->status, "load");
        $this->assertEquals(ConnectionStatus::SUCCESS, $this->status->getStatus());
    }

    public function testGetLastErrorReturnsNullByDefault()
    {
        $this->assertEquals(null, $this->status->getLastError());
    }

    public function testGetLastErrorReturnsAssignedError()
    {
        $this->callMethod($this->status, "setLastError", "something bad happened");
        $this->assertEquals("something bad happened", $this->status->getLastError());
    }

    public function testGetLastErrorDeserializedError()
    {
        $this->cache
            ->method("load")
            ->willReturn(serialize(["last_error" => "something happened"]));

        $this->callMethod($this->status, "load");
        $this->assertEquals("something happened", $this->status->getLastError());
    }

    public function testGetCompressionCountReturnsNullByDefault()
    {
        $this->assertEquals(null, $this->status->getCompressionCount());
    }

    public function testGetCompressionCountReturnsAssignedCount()
    {
        $this->callMethod($this->status, "setCompressionCount", 19);
        $this->assertEquals(19, $this->status->getCompressionCount());
    }

    public function testGetCompressionCountReturnsDeserializedCount()
    {
        $this->cache
            ->method("load")
            ->willReturn(serialize(["compression_count" => 17]));

        $this->callMethod($this->status, "load");
        $this->assertEquals(17, $this->status->getCompressionCount());
    }

    public function testUpdateSetsSuccessData()
    {
        Tinify\setKey("my_key");
        Tinify\Tinify::setCompressionCount(9);

        $this->config
            ->method("apply")
            ->willReturn(true);

        AspectMock\Test::double("Tinify\Client", [
            "request" => function () {
                throw new Tinify\ClientException("error");
            }
        ]);

        $this->cache
            ->expects($this->once())
            ->method("save")
            ->with(serialize([
                "status" => 1,
                "compression_count" => 9,
            ]), "tinify_status");

        $this->status->update();
    }

    public function testUpdateSetsFailureData()
    {
        Tinify\setKey("my_key");
        Tinify\Tinify::setCompressionCount(9);

        $this->config
            ->method("apply")
            ->willReturn(true);

        AspectMock\Test::double("Tinify\Client", [
            "request" => function () {
                throw new Tinify\AccountException("error");
            }
        ]);

        $this->cache
            ->expects($this->once())
            ->method("save")
            ->with(serialize([
                "status" => 2,
                "last_error" => "error",
                "compression_count" => 9,
            ]), "tinify_status");

        $this->status->update();
    }

    public function testUpdateSetsUnexpectedFailureData()
    {
        Tinify\setKey("my_key");

        $this->config
            ->method("apply")
            ->willReturn(true);

        AspectMock\Test::double("Tinify\Client", [
            "request" => function () {
                throw new \Exception("unexpected error");
            }
        ]);

        $this->cache
            ->expects($this->once())
            ->method("save")
            ->with(serialize([
                "status" => 2,
                "last_error" => "unexpected error",
            ]), "tinify_status");

        $this->status->update();
    }

    public function testUpdateSetsUnknownData()
    {
        $this->config
            ->method("apply")
            ->willReturn(false);

        $this->cache
            ->expects($this->once())
            ->method("save")
            ->with(serialize([
                "status" => 0,
            ]), "tinify_status");

        $this->status->update();
    }

    public function testLoadIgnoresBotchedData()
    {
        $this->cache
            ->method("load")
            ->willReturn("some garbage here");

        $this->callMethod($this->status, "load");
        $this->assertEquals(ConnectionStatus::UNKNOWN, $this->status->getStatus());
    }
}
