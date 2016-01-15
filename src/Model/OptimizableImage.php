<?php

namespace Tinify\Magento\Model;

use Tinify;
use Tinify\Magento\Model\Config;

use Magento\Catalog\Model\Product\Image;
use Psr\Log\LoggerInterface as Logger;

class OptimizableImage
{
    protected $logger;
    protected $config;
    protected $image;

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
        if (!$this->isOptimizable()) {
            $this->logger->debug("Skipping {$this->getUnoptimizedPath()}.");
            return false;
        }

        if (!$this->config->apply()) {
            $this->logger->debug("API key not configured.");
            return false;
        }

        $dir = $this->config->getMediaDirectory();
        $path = $this->getOptimizedPath();

        if (!$dir->isFile($path)) {
            $source = $dir->readFile($this->getUnoptimizedPath());

            try {
                $result = Tinify\fromBuffer($source)->toBuffer();
                $this->config->getStatus()->updateCompressionCount();
            } catch (Tinify\Exception $err) {
                $this->logger->error($err);
                return false;
            }

            $dir->writeFile($path, $result);
            $this->logger->debug("Optimized {$this->getUnoptimizedPath()}.");
        }

        return true;
    }

    protected function isOptimizable()
    {
        switch (strtolower($this->image->getDestinationSubdir())) {
            case "thumbnail":
                $type = "thumbnail";
                break;
            case "small_image":
                $type = "small";
                break;
            case "swatch_thumb":
            case "swatch_image":
                $type = "swatch";
                break;
            case "image":
            default:
                $type = "base";
        }

        return $this->config->isOptimizableType($type);
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
