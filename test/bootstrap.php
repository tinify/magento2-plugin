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

class SqlImporter
{
    protected $fs;
    protected $rc;

    public function __construct(File $fs, ResourceConnection $rc)
    {
        $this->fs = $fs;
        $this->rc = $rc;
    }

    public function import($file)
    {
        $name = "magento2_test";
        exec("mysql -u root -e 'drop database if exists {$name}; create database {$name}'");
        $sql = $this->fs->fileGetContents(__DIR__ . "/fixtures/" . $file);
        $this->rc->getConnection()->exec($sql);
    }
}

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
}

abstract class IntegrationTestCase extends TestCase
{
    protected static $imported;

    protected function setUp()
    {
        if (!self::$imported) {
            $importer = $this->getObjectManager()->get("Tinify\Magento\SqlImporter");
            $importer->import("magento.sql");
            self::$imported = true;
        }
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
                            "host" => "localhost",
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
