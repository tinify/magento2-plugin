<?php

namespace Tinify\Magento\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Model\Product\Image;

class OptimizableImageFactory
{
    const INSTANCE = __NAMESPACE__ . "\\OptimizableImage";

    protected $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(Image $image)
    {
        return $this->objectManager->create(self::INSTANCE, ["image" => $image]);
    }
}
