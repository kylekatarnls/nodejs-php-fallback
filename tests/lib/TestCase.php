<?php

namespace NodejsPhpFallbackTest;

use Composer\Composer;
use Composer\Config;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
{
    protected static $deleteAfterTest = array();

    protected static function getVendorDir()
    {
        return sys_get_temp_dir() . '/NodejsPhpFallbackVendor';
    }

    protected function emulateComposer($packages)
    {
        $vendorDir = static::getVendorDir();
        static::removeDirectory($vendorDir);
        $requires = array();
        foreach ($packages as $package => $settings) {
            @mkdir($vendorDir . '/' . $package, 0777, true);
            if ($settings) {
                file_put_contents($vendorDir . '/' . $package . '/composer.json', $settings);
            }
            $requires[$package] = array();
        }
        $package = new RootPackage('bin', '1.0.0', '1.0.0');
        $package->setRequires($requires);
        $composer = new Composer();
        $config = new Config();
        $config->merge(array(
            'config' => array(
                'vendor-dir' => $vendorDir,
            ),
        ));
        $composer->setConfig($config);
        $composer->setPackage($package);

        return $composer;
    }

    protected function appDirectory()
    {
        static $directory = null;

        if (is_null($directory)) {
            $directory = dirname(dirname(__DIR__));
        }

        return $directory;
    }

    protected static function removeDirectory($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (is_dir($dir . '/' . $object)) {
                        static::removeDirectory($dir . '/' . $object);
                        continue;
                    }
                    // move before delete to avoid Windows too long name error
                    try {
                        @rename($dir . '/' . $object, sys_get_temp_dir() . '/to-delete');
                        @unlink(sys_get_temp_dir() . '/to-delete');
                    } catch (\Exception $e) {
                    }
                }
            }
            @rmdir($dir);
        }
        if (is_file($dir)) {
            @unlink($dir);
        }
    }

    public static function removeTestDirectories()
    {
        foreach (static::$deleteAfterTest as $directory) {
            static::removeDirectory(__DIR__ . '/../../' . $directory);
        }
        static::removeDirectory(static::getVendorDir());
    }
}
