<?php

namespace Tinify\Magento\Model;

use Tinify;

class ConfigTest extends \Tinify\Magento\TestCase
{
    protected function setUp()
    {
        $this->coreConfig = $this->getMock(
            "Magento\Framework\App\Config\ScopeConfigInterface"
        );

        $this->mediaConfig = $this->getMock(
            "Magento\Catalog\Model\Product\Media\ConfigInterface"
        );

        $this->mediaDir = $this->getMock(
            "Magento\Framework\Filesystem\Directory\WriteInterface"
        );

        $this->filesystem = $this
            ->getMockBuilder("Magento\Framework\Filesystem")
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem
            ->method("getDirectoryWrite")
            ->willReturn($this->mediaDir);

        $this->mediaConfig
            ->method("getBaseMediaPath")
            ->willReturn("catalog/product");

        $this->mediaConfig
            ->method("getMediaUrl")
            ->will($this->returnCallback(function ($path) {
                return "http://localhost/pub/media/catalog/product/" . $path;
            }));

        $this->magentoInfo = $this->getObjectManager()->getObject(
            "Magento\Framework\App\ProductMetadata"
        );

        $this->config = $this->getObjectManager()->getObject(
            "Tinify\Magento\Model\Config",
            [
                "magentoInfo" => $this->magentoInfo,
                "config" => $this->coreConfig,
                "mediaConfig" => $this->mediaConfig,
                "filesystem" => $this->filesystem,
            ]
        );
    }

    public function testHasKeyReturnsTrueIfKeyIsSet()
    {
        $this->coreConfig
            ->method("getValue")
            ->with("tinify_compress_images/general/key")
            ->willReturn("  my_key  ");

        $this->assertTrue($this->config->HasKey());
    }

    public function testHasKeyReturnsFalseIfKeyIsUnset()
    {
        $this->coreConfig
            ->method("getValue")
            ->with("tinify_compress_images/general/key")
            ->willReturn("  ");

        $this->assertFalse($this->config->HasKey());
    }

    public function testIsOptimizableTypeReturnsTrueIfTypeIsEnabled()
    {
        $this->coreConfig
            ->method("isSetFlag")
            ->with("tinify_compress_images/types/thumbnail")
            ->willReturn(true);

        $this->assertTrue($this->config->isOptimizableType("thumbnail"));
    }

    public function testIsOptimizableTypeReturnsFalseIfTypeIsDisabled()
    {
        $this->coreConfig
            ->method("isSetFlag")
            ->with("tinify_compress_images/types/thumbnail")
            ->willReturn(false);

        $this->assertFalse($this->config->isOptimizableType("thumbnail"));
    }

    public function testApplySetsTrimmedKey()
    {
        $my_key = "there_are_many_like_it_but_this_one_is_mine";

        $this->coreConfig
            ->method("getValue")
            ->with("tinify_compress_images/general/key")
            ->willReturn("  " . $my_key . "  ");

        $this->config->apply();
        $this->assertEquals($my_key, $this->getProperty("Tinify\Tinify", "key"));
    }

    public function testApplySetsVersionString()
    {
        $id = "Magento/{$this->magentoInfo->getVersion()} (Community)";

        $this->config->apply();
        $this->assertEquals($id, $this->getProperty("Tinify\Tinify", "appIdentifier"));
    }

    public function testApplyReturnsTrueIfApplied()
    {
        $this->coreConfig
            ->method("getValue")
            ->with("tinify_compress_images/general/key")
            ->willReturn("  my_key  ");

        $this->assertTrue($this->config->apply());
    }

    public function testApplyReturnsTrueIfPreviouslyApplied()
    {
        $this->coreConfig
            ->method("getValue")
            ->with("tinify_compress_images/general/key")
            ->willReturn("  my_key  ");

        $this->config->apply();
        $this->assertTrue($this->config->apply());
    }

    public function testApplyReturnsFalseIfKeyIsUnset()
    {
        $this->coreConfig
            ->method("getValue")
            ->with("tinify_compress_images/general/key")
            ->willReturn("   ");

        $this->assertFalse($this->config->apply());
    }

    public function testGetPathPrefixReturnsPrefixString()
    {
        $prefix = "catalog/product/optimized";

        $this->assertEquals($prefix, $this->config->getPathPrefix());
    }

    public function testGetMediaUrlReturnsUrl()
    {
        $file = "m/y/my_image.jpg";
        $url = "http://localhost/pub/media/catalog/product/" . $file;

        $this->assertEquals($url, $this->config->getMediaUrl($file));
    }

    public function testGetMediaUrlReturnsUrlWithoutDuplicatePrefix()
    {
        $file = "catalog/product/optimized/m/y/my_image.jpg";
        $url = "http://localhost/pub/media/catalog/product/optimized/m/y/my_image.jpg";

        $this->assertEquals($url, $this->config->getMediaUrl($file));
    }

    public function testGetMediaPathReturnsPath()
    {
        $file = "m/y/my_image.jpg";
        $path = "/tmp/media/" . $file;

        $this->mediaDir
            ->method("getAbsolutePath")
            ->with($file)
            ->willReturn($path);

        $this->assertEquals($path, $this->config->getMediaPath($file));
    }

    public function testGetMediaDirectoryReturnsDirectory()
    {
        $this->assertSame($this->mediaDir, $this->config->getMediaDirectory());
    }
}
