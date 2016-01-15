<?php

namespace Tinify\Magento\Model\Config;

use Tinify;
use Tinify\Magento\Model\Config;

use Magento\Framework\App\CacheInterface as Cache;

class ConnectionStatus
{
    const CACHE_KEY = "tinify_status";

    protected $config;
    protected $cache;
    protected $data;

    const UNKNOWN = 0;
    const SUCCESS = 1;
    const FAILURE = 2;

    public function __construct(Config $config, Cache $cache)
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->load();
    }

    public function update()
    {
        $this->setStatus(self::UNKNOWN);

        if ($this->config->apply()) {
            try {
                Tinify\validate();
                $this->setStatus(self::SUCCESS);
                $this->setLastError(null);
            } catch (\Exception $err) {
                $this->setStatus(self::FAILURE);
                $this->setLastError($err->getMessage());
            }
            $this->setCompressionCount(Tinify\getCompressionCount());
        }
        $this->save();
    }

    public function updateCompressionCount()
    {
        $this->setCompressionCount(Tinify\getCompressionCount());
        $this->save();
    }

    public function getStatus()
    {
        if (isset($this->data["status"])) {
            return $this->data["status"];
        } else {
            return self::UNKNOWN;
        }
    }

    protected function setStatus($status)
    {
        if (isset($status)) {
            $this->data["status"] = $status;
        } else {
            unset($this->data["status"]);
        }
    }

    public function getLastError()
    {
        if (isset($this->data["last_error"])) {
            return $this->data["last_error"];
        }
    }

    protected function setLastError($error)
    {
        if (isset($error)) {
            $this->data["last_error"] = $error;
        } else {
            unset($this->data["last_error"]);
        }
    }

    public function getCompressionCount()
    {
        if (isset($this->data["compression_count"])) {
            return $this->data["compression_count"];
        }
    }

    protected function setCompressionCount($count)
    {
        if (isset($count)) {
            $this->data["compression_count"] = $count;
        } else {
            unset($this->data["compression_count"]);
        }
    }

    protected function save()
    {
        $this->cache->save(serialize($this->data), self::CACHE_KEY);
    }

    protected function load()
    {
        $data = @unserialize($this->cache->load(self::CACHE_KEY));
        $this->data = is_array($data) ? $data : [];
    }
}
