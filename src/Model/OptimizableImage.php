<?php

namespace Tinify\Magento\Model;

use Tinify;
use Tinify\Magento\Model\Config;

use Magento\Catalog\Model\Product\Image;
use Magento\Swatches\Model\Swatch;
use Psr\Log\LoggerInterface as Logger;

class OptimizableImage
{
    protected $logger;
    protected $config;
    protected $image;
    protected $configured = false;

    public function __construct(Logger $logger, Config $config, Image $image)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->image = $image;
    }

    public function getUrl()
    {
        $dir = $this->config->getMediaDirectory();
        $path = $this->getOptimizedPath();

        /* Fall back to unoptimized version if optimized one does not exist. */
        if (!$dir->isFile($path)) {
            $path = $this->getUnoptimizedPath();
        }

        return $this->config->getMediaUrl($path);
    }

    public function optimize()
    {
        if (!$this->optimizable()) {
            $this->logger->debug("Skipping {$this->getUnoptimizedPath()}.");
            return false;
        }

        if (!$this->configure()) {
            $this->logger->debug("No API key configured.");
            return false;
        }

        $dir = $this->config->getMediaDirectory();
        $path = $this->getOptimizedPath();

        if (!$dir->isFile($path)) {
            $source = $dir->readFile($this->getUnoptimizedPath());

            try {
                $result = Tinify\fromBuffer($source)->toBuffer();
            } catch (Tinify\Exception $err) {
                $this->logger->error($err);
                return false;
            }

            $dir->writeFile($path, $result);
            $this->logger->debug("Optimized {$this->getUnoptimizedPath()}.");
        }

        return true;
    }

    public function optimizable()
    {
        $type = $this->image->getDestinationSubdir();
        return (
            $type !== Swatch::SWATCH_IMAGE_NAME &&
            $type !== Swatch::SWATCH_THUMBNAIL_NAME
        );
    }

    protected function configure()
    {
        if ($this->configured) {
            return true;
        }

        $key = trim($this->config->getKey());
        if (empty($key)) {
            return false;
        }

        Tinify\setKey($key);
        Tinify\setAppIdentifier($this->config->getMagentoId());
        $this->configured = true;

        return true;
    }

    protected function getOptimizedPath()
    {
        $file = $this->getUnoptimizedHash() . "." . $this->getExtension();
        return implode("/", [$this->config->getPathPrefix(), $file[0], $file[1], $file]);
    }

    protected function getUnoptimizedPath()
    {
        return $this->image->getNewFile();
    }

    protected function getUnoptimizedHash()
    {
        $file = $this->config->getMediaPath($this->getUnoptimizedPath());
        return hash_file("sha256", $file);
    }

    protected function getExtension()
    {
        return pathinfo($this->getUnoptimizedPath(), PATHINFO_EXTENSION);
    }
}
