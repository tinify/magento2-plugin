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

        $this->status = $this
            ->getMockBuilder("Tinify\Magento\Model\Config\ConnectionStatus")
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this
            ->getMockBuilder("Tinify\Magento\Model\Config")
            ->disableOriginalConstructor()
            ->getMock();

        $this->config
            ->method("getStatus")
            ->willReturn($this->status);

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

        $sha = "556481349e6e45717387cfe9a53981057e4e9be90532c7cf2d0aa7aaeb5eaf52";
        $url = "http://localhost/pub/media/catalog/product/optimized/5/5/{$sha}/my_image.jpg";
        $this->assertEquals($url, $this->optimizableImage->getUrl());
    }

    public function testGetUrlReturnsUrlBasedOnHashIfFileExistsWithMagento216()
    {
        if (!class_exists("Magento\Catalog\Model\View\Asset\Image")) {
            return;
        }

        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $imageAsset = $this
            ->getMockBuilder("Magento\Catalog\Model\View\Asset\Image")
            ->disableOriginalConstructor()
            ->getMock();

        $imageAsset
            ->method("getPath")
            ->willReturn("vfs://root/media/catalog/product/cache/1/image/60x60/my_image.jpg");

        $this->mediaDir
            ->method("getRelativePath")
            ->willReturn("catalog/product/cache/1/image/60x60/my_image.jpg");

        $class = new \ReflectionClass($this->image);
        $property = $class->getParentClass()->getProperty("imageAsset");
        $property->setAccessible(true);
        $property->setValue($this->image, $imageAsset);

        $this->image
            ->method("getNewFile")
            ->willReturn(null);

        $this->config
            ->method("getPathPrefix")
            ->willReturn("catalog/product/optimized");

        $this->mediaDir
            ->method("isFile")
            ->willReturn(true);

        $path = $this->config->getMediaPath($file);
        mkdir(dirname($path), 0777, true);
        file_put_contents($path, "suboptimial image");

        $sha = "556481349e6e45717387cfe9a53981057e4e9be90532c7cf2d0aa7aaeb5eaf52";
        $url = "http://localhost/pub/media/catalog/product/optimized/5/5/{$sha}/my_image.jpg";
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

    public function testGetUrlReturnsOriginalUrlIfFileDoesNotExistWithMagento216()
    {
        if (!class_exists("Magento\Catalog\Model\View\Asset\Image")) {
            return;
        }

        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $imageAsset = $this
            ->getMockBuilder("Magento\Catalog\Model\View\Asset\Image")
            ->disableOriginalConstructor()
            ->getMock();

        $imageAsset
            ->method("getPath")
            ->willReturn("vfs://root/media/catalog/product/cache/1/image/60x60/my_image.jpg");

        $this->mediaDir
            ->method("getRelativePath")
            ->willReturn("catalog/product/cache/1/image/60x60/my_image.jpg");

        $class = new \ReflectionClass($this->image);
        $property = $class->getParentClass()->getProperty("imageAsset");
        $property->setAccessible(true);
        $property->setValue($this->image, $imageAsset);

        $this->image
            ->method("getNewFile")
            ->willReturn(null);

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

    public function testOptimizeReturnsIfUnconfigured()
    {
        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $this->config
            ->method("isOptimizableType")
            ->willReturn(true);

        $this->config
            ->method("apply")
            ->willReturn(false);

        $this->assertFalse($this->optimizableImage->optimize());
    }

    public function testOptimizeLogsMessageIfUnconfigured()
    {
        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $this->config
            ->method("isOptimizableType")
            ->willReturn(true);

        $this->config
            ->method("apply")
            ->willReturn(false);

        $this->logger
            ->expects($this->once())
            ->method("debug")
            ->with("API key not configured.");

        $this->optimizableImage->optimize();
    }

    public function testOptimizeReturnsIfImageTypeIsNotOptimizable()
    {
        $file = "catalog/product/cache/1/image/60x60/my_image.jpg";

        $this->image
            ->method("getNewFile")
            ->willReturn($file);

        $this->config
            ->expects($this->once())
            ->method("isOptimizableType")
            ->with("base")
            ->willReturn(false);

        $this->config
            ->method("apply")
            ->willReturn(true);

        $this->assertFalse($this->optimizableImage->optimize());
    }

    public function testOptimizeReturnsIfImageTypeWithAliasIsNotOptimizable()
    {
        $file = "catalog/product/cache/1/swatch_thumb/60x60/my_image.jpg";

        $this->image
            ->method("getNewFile")
            ->willReturn($file);

        $this->image
            ->method("getDestinationSubdir")
            ->willReturn("swatch_thumb");

        $this->config
            ->expects($this->once())
            ->method("isOptimizableType")
            ->with("swatch")
            ->willReturn(false);

        $this->config
            ->method("apply")
            ->willReturn(true);

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
            ->method("apply")
            ->willReturn(true);

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
            ->method("apply")
            ->willReturn(true);

        $path = $this->config->getMediaPath($file);
        mkdir(dirname($path), 0777, true);
        file_put_contents($path, "suboptimial image");

        $sha = "556481349e6e45717387cfe9a53981057e4e9be90532c7cf2d0aa7aaeb5eaf52";
        $path = "catalog/product/optimized/5/5/{$sha}/my_image.jpg";

        $this->mediaDir
            ->expects($this->once())
            ->method("writeFile")
            ->with($path, "optimal file binary");

        $this->optimizableImage->optimize();
    }

    public function testOptimizeUpdatesCompressionCountIfKeyIsSet()
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
            ->method("apply")
            ->willReturn(true);

        $path = $this->config->getMediaPath($file);
        mkdir(dirname($path), 0777, true);
        file_put_contents($path, "suboptimial image");

        $this->status
            ->expects($this->once())
            ->method("updateCompressionCount");

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
            ->method("apply")
            ->willReturn(true);

        $this->mediaDir
            ->method("isFile")
            ->willReturn(true);

        $path = $this->config->getMediaPath($file);
        mkdir(dirname($path), 0777, true);
        file_put_contents($path, "suboptimial image");

        $sha = "556481349e6e45717387cfe9a53981057e4e9be90532c7cf2d0aa7aaeb5eaf52";
        $path = "catalog/product/optimized/{$sha}/my_image.jpg";

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
            ->method("apply")
            ->willReturn(true);

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
