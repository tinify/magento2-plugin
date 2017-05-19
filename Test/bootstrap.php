<?php

namespace Tinify\Magento;

use Magento\Framework\Autoload;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
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

if (file_exists(BP . "/lib/internal/Cm/Cache/Backend/File.php")) {
    /* TODO: Figure out how this class should be autoloaded? */
    /* This used to be necessary for Magento 2.0.x. */
    require_once(BP . "/lib/internal/Cm/Cache/Backend/File.php");
}

require_once(BP . "/app/functions.php");

class FixedImage extends \Magento\Framework\Image
{
    public function save($destination = null, $newFileName = null)
    {
        /* Work around a bug where a / is accidentally prepended to absolute
           paths in Magento\Catalog\Model\View\Asset\Image::getRelativePath() */
        $destination = str_replace("/vfs:", "vfs:", $destination);
        parent::save($destination, $newFileName);
    }
}

if (class_exists("Magento\Catalog\Model\View\Asset\Image")) {
    class FixedAssetImage extends \Magento\Catalog\Model\View\Asset\Image
    {
        public function getPath()
        {
            /* Work around a bug where a / is accidentally prepended to absolute
               paths in Magento\Catalog\Model\View\Asset\Image::getRelativePath() */
            return str_replace("/vfs:", "vfs:", parent::getPath());
        }
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

    protected function getProperty($object, $name)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    protected function setProperty($object, $name, $value)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        return $property->setValue($object, $value);
    }

    protected function callMethod($object, $name)
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($object, array_slice(func_get_args(), 2));
    }
}

abstract class IntegrationTestCase extends TestCase
{
    protected static $imported;
    protected $useRoot = false;

    protected function setUp()
    {
        if (!self::$imported) {
            exec("mysql -u root < Test/fixtures/magento.sql", $output, $result);
            if ($result !== 0) exit($result);
            self::$imported = true;
        }
    }

    protected function useFilesystemRoot()
    {
        $this->useRoot = true;
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

        if (class_exists("Magento\Catalog\Model\View\Asset\Image")) {
            $this->getObjectManager()->configure(array(
                "preferences" => array(
                    "Magento\Framework\Image" => "Tinify\Magento\FixedImage",
                    "Magento\Catalog\Model\View\Asset\Image" => "Tinify\Magento\FixedAssetImage",
                )
            ));
        } else {
            $this->getObjectManager()->configure(array(
                "preferences" => array(
                    "Magento\Framework\Image" => "Tinify\Magento\FixedImage",
                )
            ));
        }
    }

    protected function constructObjectManager()
    {
        global $autoloader;
        $magentoAutoloader = new Autoload\ClassLoaderWrapper($autoloader);
        Autoload\AutoloaderRegistry::registerAutoloader($magentoAutoloader);

        /* Overwrite all directories that are used by our module. A correct
           config path is required for a working object manager. */

        $dirList = new DirectoryList($this->useRoot ? BP : $this->getVfs(), [
            DirectoryList::CONFIG  => ["path" => BP . "/app/etc"],
            DirectoryList::MEDIA   => ["path" => $this->getVfs() . "/media"],
            DirectoryList::VAR_DIR => ["path" => $this->getVfs() . "/var"],
            DirectoryList::CACHE   => ["path" => BP . "/cache"],
        ]);

        Autoload\Populator::populateMappings(
            $magentoAutoloader, $dirList, new ComponentRegistrar()
        );

        $factory = new ObjectManagerFactory(
            $dirList, new DriverPool(), new ConfigFilePool()
        );

        $config = [
            State::PARAM_MODE => State::MODE_DEVELOPER,
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
                    "Magento_Authorization" => 1,
                    "Magento_Backend" => 1,
                    "Magento_Config" => 1,
                    "Magento_Catalog" => 1,
                    "Magento_Developer" => 1,
                    "Magento_Email" => 1,
                    "Magento_MediaStorage" => 1,
                    "Magento_Store" => 1,
                    "Magento_Theme" => 1,
                    "Magento_Translation" => 1,
                    "Magento_Ui" => 1,
                    "Tinify_CompressImages" => 1,
                ],
            ]
        ];

        $objectManager = $factory->create($config);

        return $objectManager;
    }
}
