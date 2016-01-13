<?php

namespace Tinify\Magento\Plugin;

use Tinify\Magento\Model\OptimizableImageFactory;

use Magento\Catalog\Model\Product\Image;

class ImagePlugin
{
    /* Fixed quality with which source images will be stored. */
    const SOURCE_QUALITY = 85;

    protected $factory;

    public function __construct(OptimizableImageFactory $factory)
    {
        $this->factory = $factory;
    }

    public function beforeSaveFile(Image $image)
    {
        $image->setQuality(self::SOURCE_QUALITY);
    }

    public function afterSaveFile(Image $image)
    {
        return $this->factory->create($image)->optimize();
    }

    public function afterGetUrl(Image $image, $origUrl)
    {
        return $this->factory->create($image)->getUrl();
    }
}
