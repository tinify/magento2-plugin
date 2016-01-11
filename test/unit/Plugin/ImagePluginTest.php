<?php

namespace Tinify\Magento\Plugin;

class ImagePluginTest extends \Tinify\Magento\TestCase
{
    protected $image;
    protected $optimizableImage;
    protected $factory;
    protected $plugin;

    protected function setUp()
    {
        $this->image = $this
            ->getMockBuilder("Magento\Catalog\Model\Product\Image")
            ->disableOriginalConstructor()
            ->getMock();

        $this->optimizableImage = $this
            ->getMockBuilder("Tinify\Magento\Model\OptimizableImage")
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = $this
            ->getMockBuilder("Tinify\Magento\Model\OptimizableImageFactory")
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory
            ->method("create")
            ->willReturn($this->optimizableImage);

        $this->plugin = $this->getObjectManager()->getObject(
            "Tinify\Magento\Plugin\ImagePlugin",
            [
                "factory" => $this->factory,
            ]
        );
    }

    public function testBeforeSaveFileSetsFixedQuality()
    {
        $this->image
            ->expects($this->once())
            ->method("setQuality")
            ->with(85);

        $this->plugin->beforeSaveFile($this->image);
    }

    public function testAfterSaveFileCallsOptimize()
    {
        $this->optimizableImage
            ->expects($this->once())
            ->method("optimize");

        $this->plugin->afterSaveFile($this->image);
    }

    public function testAfterGetUrlReturnsOptimizedUrl()
    {
        $url = "http://localhost/pub/media/catalog/product/i/m/img.jpg";

        $this->optimizableImage
            ->expects($this->once())
            ->method("getUrl")
            ->willReturn($url);

        $this->assertEquals(
            $url,
            $this->plugin->afterGetUrl($this->image, "orig_url")
        );
    }
}
