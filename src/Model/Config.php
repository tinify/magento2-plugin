<?php

namespace Tinify\Magento\Model;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\Product\Media\ConfigInterface as MediaConfigInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class Config
{
    const KEY_PATH = "tinify_compress_images/general/key";

    protected $magentoInfo;
    protected $config;
    protected $mediaConfig;
    protected $mediaDirectory;

    public function __construct(
        ProductMetadataInterface $magentoInfo,
        ScopeConfigInterface $config,
        MediaConfigInterface $mediaConfig,
        Filesystem $filesystem
    ) {
        $this->magentoInfo = $magentoInfo;
        $this->config = $config;
        $this->mediaConfig = $mediaConfig;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    public function getKey()
    {
        return $this->config->getValue(self::KEY_PATH);
    }

    public function getMagentoId()
    {
        $name = $this->magentoInfo->getName();
        $version = $this->magentoInfo->getVersion();
        $edition = $this->magentoInfo->getEdition();
        return "{$name}/{$version} ({$edition})";
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
