<?php

namespace NodejsPhpFallbackTest;

use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
{
    protected static $deleteAfterTest = array();

    protected function appDirectory()
    {
        static $directory = null;

        if (is_null($directory)) {
            $directory = dirname(dirname(__DIR__));
        }

        return $directory;
    }

    protected static function removeDirectory($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (is_dir($dir . '/' . $object)) {
                        static::removeDirectory($dir . '/' . $object);
                        continue;
                    }
                    // move before delete to avoid Windows too long name error
                    rename($dir . '/' . $object, sys_get_temp_dir() . '/to-delete');
                    unlink(sys_get_temp_dir() . '/to-delete');
                }
            }
            rmdir($dir);
        }
    }

    public static function removeTestDirectories()
    {
        foreach (static::$deleteAfterTest as $directory) {
            static::removeDirectory(__DIR__ . '/../../' . $directory);
        }
    }
}
