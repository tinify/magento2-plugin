<?php

namespace Tinify\Magento;

use AspectMock;
use Tinify;

class ImageIntegrationTest extends \Tinify\Magento\IntegrationTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $logHandler = $this->getObjectManager()->get(
            "Magento\Framework\Logger\Handler\System"
        );
        $this->setProperty($logHandler, "url", $this->getVfs() . "/system.log");

        $scopeConfig = $this->getObjectManager()->get(
            "Magento\Framework\App\Config\MutableScopeConfigInterface"
        );
        $scopeConfig->setValue(Model\Config::KEY_PATH, "my_api_key");
        $scopeConfig->setValue(Model\Config::TYPES_PATH . "/swatch", 0);

        $config = $this->getObjectManager()->get(
            "Tinify\Magento\Model\Config"
        );
        $this->dir = $config->getMediaDirectory();

        $this->dir->create();

        $this->pngSuboptimal = file_get_contents(__DIR__ . "/../fixtures/example.png");
        $this->dir->writeFile("catalog/product/example.png", $this->pngSuboptimal);

        $this->jpgSuboptimal = file_get_contents(__DIR__ . "/../fixtures/example.jpg");
        $this->dir->writeFile("catalog/product/example.jpg", $this->jpgSuboptimal);

        $this->pngOptimal = file_get_contents(__DIR__ . "/../fixtures/example-tiny.png");
        AspectMock\Test::double("Tinify\Source", [
            "fromBuffer" => new Tinify\Result([], $this->pngOptimal)
        ]);

        $this->image = $this->getObjectManager()->create(
            "Magento\Catalog\Model\Product\Image"
        );
    }

    protected function tearDown()
    {
        $this->dir->delete("catalog/product/optimized");
    }

    public function testSaveCreatesOptimizedVersion()
    {
        $this->image->setDestinationSubdir("my_image_type");
        $this->image->setBaseFile("example.png");
        $this->image->saveFile();

        $sha = "d519570140157e41611e39513acca2c79ab89b301fcb5e76178db49bc8f26fab";
        $path = "catalog/product/optimized/d/5/{$sha}.png";
        $this->assertEquals($this->pngOptimal, $this->dir->readFile($path));
    }

    public function testSaveDoesNotCreateOptimizedVersionIfDisabled()
    {
        $this->image->setDestinationSubdir("swatch_thumb");
        $this->image->setBaseFile("example.png");
        $this->image->saveFile();

        $sha = "d519570140157e41611e39513acca2c79ab89b301fcb5e76178db49bc8f26fab";
        $path = "catalog/product/optimized/d/5/{$sha}.png";
        $this->assertFalse($this->dir->isFile($path));
    }

    public function testSaveDoesNotOverwriteOptimizedVersion()
    {
        $sha = "d519570140157e41611e39513acca2c79ab89b301fcb5e76178db49bc8f26fab";
        $path = "catalog/product/optimized/d/5/{$sha}.png";
        $this->dir->writeFile($path, "previous binary");

        $this->image->setDestinationSubdir("my_image_type");
        $this->image->setBaseFile("example.png");
        $this->image->saveFile();

        $this->assertEquals("previous binary", $this->dir->readFile($path));
    }

    public function testSaveCreatesOptimizedVersionRegardlessOfQuality()
    {
        $image1 = $this->getObjectManager()->create(
            "Magento\Catalog\Model\Product\Image"
        );

        $image1->setDestinationSubdir("my_small_image1");
        $image1->setBaseFile("example.jpg");
        $image1->setWidth(200);
        $image1->setHeight(133);
        $image1->saveFile();

        $image2 = $this->getObjectManager()->create(
            "Magento\Catalog\Model\Product\Image"
        );

        $image2->setDestinationSubdir("my_small_image2");
        $image2->setBaseFile("example.jpg");
        $image2->setWidth(200);
        $image2->setHeight(133);
        $image2->setQuality(31);
        $image2->saveFile();

        $this->assertEquals($image1->getUrl(), $image2->getUrl());
    }

    public function testSaveLogsExceptionOnCompressionError()
    {
        $error = new Tinify\Exception("error");
        AspectMock\Test::double("Tinify\Source", [
            "fromBuffer" => function () use ($error) {
                throw $error;
            }
        ]);

        $this->image->setDestinationSubdir("my_image_type");
        $this->image->setBaseFile("example.png");
        $this->image->saveFile();

        $log = file_get_contents($this->getVfs() . "/system.log");
        $this->assertContains(
            "tinify.ERROR: {$error}",
            $log
        );
    }

    public function testGetUrlReturnsOptimizedVersion()
    {
        $this->image->setDestinationSubdir("my_image_type");
        $this->image->setBaseFile("example.png");
        $this->image->saveFile();

        $sha = "d519570140157e41611e39513acca2c79ab89b301fcb5e76178db49bc8f26fab";
        $url = "http://localhost:3000/pub/media/catalog/product/optimized/d/5/{$sha}.png";
        $this->assertEquals($url, $this->image->getUrl());
    }

    public function testGetUrlReturnsCachedVersionWhenKeyIsUnset()
    {
        $this->getObjectManager()->get(
            "Magento\Framework\App\Config\MutableScopeConfigInterface"
        )->setValue(Model\Config::KEY_PATH, "  ");

        $this->image->setDestinationSubdir("my_image_type");
        $this->image->setBaseFile("example.png");
        $this->image->saveFile();

        $url = "http://localhost:3000/pub/media/catalog/product/cache/1/" .
            "my_image_type/beff4985b56e3afdbeabfc89641a4582/example.png";
        $this->assertEquals($url, $this->image->getUrl());
    }
}
