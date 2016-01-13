<?php

namespace Tinify\Magento;

use Magento\Framework\Autoload;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as MockObjectManager;
use AspectMock;
use org\bovigo\vfs\vfsStream;

/* Required constant to Magento root. */
define("BP", realpath("vendor/magento/community-edition"));

/* Wrap Composer autoloader. */
$autoloader = include dirname(__DIR__) . "/vendor/autoload.php";

$kernel = AspectMock\Kernel::getInstance();
$kernel->init([
    "debug" => true,
    "includePaths" => [__DIR__ . "/../vendor/tinify"],
]);

/* TODO: Figure out how this class should be autoloaded? */
require_once(BP . "/lib/internal/Cm/Cache/Backend/File.php");
require_once(BP . "/app/functions.php");

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $vfs;
    protected $objectManager;

    protected function getVfs()
    {
        if (!$this->vfs) {
            $this->vfs = vfsStream::setup();
        }
        return $this->vfs->url();
    }

    protected function getObjectManager()
    {
        if (!$this->objectManager) {
            $this->objectManager = $this->constructObjectManager();
        }
        return $this->objectManager;
    }

    protected function constructObjectManager()
    {
        return new MockObjectManager($this);
    }

    protected function setProperty($object, $name, $value)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}

abstract class IntegrationTestCase extends TestCase
{
    protected static $imported;

    protected function setUp()
    {
        if (!self::$imported) {
            exec("mysql -u root < test/fixtures/magento.sql", $output, $result);
            if ($result !== 0) exit($result);
            self::$imported = true;
        }
    }

    protected function loadArea($code)
    {
        $state = $this->getObjectManager()->get(
            "Magento\Framework\App\State"
        );

        $configLoader = $this->getObjectManager()->get(
            "Magento\Framework\ObjectManager\ConfigLoaderInterface"
        );

        $state->setAreaCode($code);
        $this->getObjectManager()->configure($configLoader->load($code));
    }

    protected function constructObjectManager()
    {
        global $autoloader;
        $magentoAutoloader = new Autoload\ClassLoaderWrapper($autoloader);
        Autoload\AutoloaderRegistry::registerAutoloader($magentoAutoloader);

        /* Overwrite all directories that are used by our module. A correct
           config path is required for a working object manager. */
        $dirList = new DirectoryList($this->getVfs(), [
            DirectoryList::CONFIG => ["path" => BP . "/app/etc"],
            DirectoryList::MEDIA  => ["path" => $this->getVfs() . "/media"],
        ]);

        Autoload\Populator::populateMappings(
            $magentoAutoloader, $dirList, new ComponentRegistrar()
        );

        $factory = new ObjectManagerFactory(
            $dirList, new DriverPool(), new ConfigFilePool()
        );

        $config = [
            "MAGE_CONFIG" => [
                "db" => [
                    "connection" => [
                        "default" => [
                            "host" => "127.0.0.1",
                            "dbname" => "magento2_test",
                            "username" => "root",
                            "password" => "",
                        ],
                    ],
                ],
                "resource" => [
                    "default_setup" => [
                        "connection" => "default",
                    ],
                ],
                "modules" => [
                    "Magento_Backend" => 1,
                    "Magento_Store" => 1,
                    "Magento_Theme" => 1,
                    "Magento_Developer" => 1,
                    "Magento_MediaStorage" => 1,
                    "Tinify_Compress_Images" => 1,
                ],
                ]
        ];

        $objectManager = $factory->create($config);

        return $objectManager;
    }
}
