<?php

namespace Tinify\Magento\Model;

use Tinify;

use Magento\Catalog\Model\Product\Media\ConfigInterface as MediaConfig;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ProductMetadataInterface as MagentoInfo;
use Magento\Framework\Filesystem;
use Magento\Swatches\Model\Swatch;

class Config
{
    const KEY_PATH = "tinify_compress_images/general/key";
    const TYPES_PATH = "tinify_compress_images/types";

    protected $configured = false;
    protected $magentoInfo;
    protected $config;
    protected $mediaConfig;
    protected $mediaDirectory;

    public function __construct(
        MagentoInfo $magentoInfo,
        ScopeConfig $config,
        MediaConfig $mediaConfig,
        Filesystem $filesystem
    ) {
        $this->magentoInfo = $magentoInfo;
        $this->config = $config;
        $this->mediaConfig = $mediaConfig;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    protected function getKey()
    {
        return trim($this->config->getValue(self::KEY_PATH));
    }

    public function hasKey()
    {
        return !empty($this->getKey());
    }

    public function isOptimizableType($type)
    {
        return $this->config->isSetFlag(self::TYPES_PATH . "/" . $type);
    }

    public function apply()
    {
        if ($this->configured) {
            return true;
        }

        $key = $this->getKey();
        if (empty($key)) {
            return false;
        }

        Tinify\setKey($key);

        $name = $this->magentoInfo->getName();
        $version = $this->magentoInfo->getVersion();
        $edition = $this->magentoInfo->getEdition();
        Tinify\setAppIdentifier("{$name}/{$version} ({$edition})");

        return $this->configured = true;
    }

    public function getPathPrefix()
    {
        return $this->mediaConfig->getBaseMediaPath() . "/optimized";
    }

    public function getMediaUrl($path)
    {
        /* Remove catalog/product prefix, it's re-added by getMediaUrl(). */
        $prefix = $this->mediaConfig->getBaseMediaPath() . "/";
        if (substr($path, 0, strlen($prefix)) == $prefix) {
            $path = substr($path, strlen($prefix));
        }
        return $this->mediaConfig->getMediaUrl($path);
    }

    public function getMediaPath($path)
    {
        return $this->mediaDirectory->getAbsolutePath($path);
    }

    public function getMediaDirectory()
    {
        return $this->mediaDirectory;
    }
}
