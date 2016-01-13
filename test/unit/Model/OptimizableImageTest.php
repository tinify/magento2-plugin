<?php

namespace Tinify\Magento\Model;

use AspectMock;
use Tinify;

class OptimizableImageTest extends \Tinify\Magento\TestCase
{
    protected function setUp()
    {
        $this->logger = $this->getMock(
            "Psr\Log\LoggerInterface"
        );

        $this->mediaDir = $this->getMock(
            "Magento\Framework\Filesystem\Directory\WriteInterface"
        );

        $this->config = $this
            ->getMockBuilder("Tinify\Magento\Model\Config")
            ->disableOriginalConstructor()
            ->getMock();

        $this->config
            ->method("getMediaPath")
            ->will($this->returnCallback(function ($path) {
                return $this->getVfs() . "/tmp/media/" . $path;
            }));

        $this->config
            ->method("getMediaUrl")
            ->will($this->returnCallback(function ($path) {
                $prefix = "catalog/product/";
                if (substr($path, 0, strlen($prefix)) !== $prefix) {
                    $path = $prefix . $path;
                }
                return "http://localhost/pub/media/" . $path;
            }));

        $this->config
            ->method("getMediaDirectory")
            ->willReturn($this->mediaDir);

        $this->image = $this
            ->getMockBuilder("Magento\Catalog\Model\Product\Image")
            ->disableOriginalConstructor()
            ->getMock();

        $this->optimizableImage = $this->getObjectManager()->getObject(
            "Tinify\Magento\Model\OptimizableImage",
            [
                "logger" => $this->logger,
                "config" => $this->config,
                "image" => $this->image,
            ]
        );

        AspectMock\Test::double("Tinify\Source", [
            "fromBuffer" => new Tinify\Result([], "optimal file binary")
        ]);
    }

    public function testGetUrlReturnsUrlBasedOnHashIfFileExists()
    {
        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $this->image
            ->method("getNewFile")
            ->willReturn($file);

        $this->config
            ->method("getPathPrefix")
            ->willReturn("catalog/product/optimized");

        $this->mediaDir
            ->method("isFile")
            ->willReturn(true);

        $path = $this->config->getMediaPath($file);
        mkdir(dirname($path), 0777, true);
        file_put_contents($path, "suboptimial image");

        $url = "http://localhost/pub/media/catalog/product/optimized/" .
            "5/5/556481349e6e45717387cfe9a53981057e4e9be90532c7cf2d0aa7aaeb5eaf52.jpg";
        $this->assertEquals($url, $this->optimizableImage->getUrl());
    }

    public function testGetUrlReturnsOriginalUrlIfFileDoesNotExist()
    {
        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $this->image
            ->method("getNewFile")
            ->willReturn($file);

        $this->config
            ->method("getPathPrefix")
            ->willReturn("catalog/product/optimized");

        $this->mediaDir
            ->method("isFile")
            ->willReturn(false);

        $path = $this->config->getMediaPath($file);
        mkdir(dirname($path), 0777, true);
        file_put_contents($path, "suboptimial image");

        $url = "http://localhost/pub/media/catalog/product/cache/1/image/60x60/my_image.jpg";
        $this->assertEquals($url, $this->optimizableImage->getUrl());
    }

    public function testOptimizeReturnsIfKeyIsUnset()
    {
        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $this->config
            ->method("isOptimizableType")
            ->willReturn(true);

        $this->config
            ->method("getKey")
            ->willReturn("");

        $this->assertFalse($this->optimizableImage->optimize());
    }

    public function testOptimizeLogsMessageIfKeyIsUnset()
    {
        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $this->config
            ->method("isOptimizableType")
            ->willReturn(true);

        $this->config
            ->method("getKey")
            ->willReturn("");

        $this->logger
            ->expects($this->once())
            ->method("debug")
            ->with("No API key configured.");

        $this->optimizableImage->optimize();
    }

    public function testOptimizeReturnsIfImageTypeIsNotOptimizable()
    {
        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $this->config
            ->expects($this->once())
            ->method("isOptimizableType")
            ->with("base")
            ->willReturn(false);

        $this->config
            ->method("getKey")
            ->willReturn("my_valid_key");

        $this->assertFalse($this->optimizableImage->optimize());
    }

    public function testOptimizeReturnsIfImageTypeWithAliasIsNotOptimizable()
    {
        $file = "catalog/product/cache/1/swatch_thumb/60x60/my_image.jpg";

        $this->image
            ->method("getDestinationSubdir")
            ->willReturn("swatch_thumb");

        $this->config
            ->expects($this->once())
            ->method("isOptimizableType")
            ->with("swatch")
            ->willReturn(false);

        $this->config
            ->method("getKey")
            ->willReturn("my_valid_key");

        $this->assertFalse($this->optimizableImage->optimize());
    }

    public function testOptimizeLogsMessageIfImageIsNotOptimizable()
    {
        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $this->image
            ->method("getNewFile")
            ->willReturn($file);

        $this->config
            ->method("isOptimizableType")
            ->willReturn(false);

        $this->config
            ->method("getKey")
            ->willReturn("my_valid_key");

        $this->logger
            ->expects($this->once())
            ->method("debug")
            ->with("Skipping {$file}.");

        $this->optimizableImage->optimize();
    }

    public function testOptimizeCompressesCachedFileIfKeyIsSet()
    {
        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $this->image
            ->method("getNewFile")
            ->willReturn($file);

        $this->config
            ->method("getPathPrefix")
            ->willReturn("catalog/product/optimized");

        $this->config
            ->method("isOptimizableType")
            ->willReturn(true);

        $this->config
            ->method("getKey")
            ->willReturn("my_valid_key");

        $path = $this->config->getMediaPath($file);
        mkdir(dirname($path), 0777, true);
        file_put_contents($path, "suboptimial image");

        $path = "catalog/product/optimized/" .
            "5/5/556481349e6e45717387cfe9a53981057e4e9be90532c7cf2d0aa7aaeb5eaf52.jpg";

        $this->mediaDir
            ->expects($this->once())
            ->method("writeFile")
            ->with($path, "optimal file binary");

        $this->optimizableImage->optimize();
    }

    public function testOptimizeDoesNothingIfCompressedFileExists()
    {
        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $this->image
            ->method("getNewFile")
            ->willReturn($file);

        $this->config
            ->method("getPathPrefix")
            ->willReturn("catalog/product/optimized");

        $this->config
            ->method("isOptimizableType")
            ->willReturn(true);

        $this->config
            ->method("getKey")
            ->willReturn("my_valid_key");

        $this->mediaDir
            ->method("isFile")
            ->willReturn(true);

        $path = $this->config->getMediaPath($file);
        mkdir(dirname($path), 0777, true);
        file_put_contents($path, "suboptimial image");

        $path = "catalog/product/optimized/" .
            "5/5/556481349e6e45717387cfe9a53981057e4e9be90532c7cf2d0aa7aaeb5eaf52.jpg";

        $this->mediaDir
            ->expects($this->never())
            ->method("writeFile");

        $this->optimizableImage->optimize();
    }

    public function testOptimizeLogsExceptionOnCompressionError()
    {
        $error = new Tinify\Exception("error");
        AspectMock\Test::double("Tinify\Source", [
            "fromBuffer" => function () use ($error) {
                throw $error;
            }
        ]);

        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $this->image
            ->method("getNewFile")
            ->willReturn($file);

        $this->config
            ->method("isOptimizableType")
            ->willReturn(true);

        $this->config
            ->method("getKey")
            ->willReturn("my_valid_key");

        $path = $this->config->getMediaPath($file);
        mkdir(dirname($path), 0777, true);
        file_put_contents($path, "suboptimial image");

        $this->logger
            ->expects($this->once())
            ->method("error")
            ->with($error);

        $this->optimizableImage->optimize();
    }
}
