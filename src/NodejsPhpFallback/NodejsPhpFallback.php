<?php

namespace NodejsPhpFallback;

use Composer\Script\Event;

class NodejsPhpFallback
{
    protected $nodePath;

    public function __construct($nodePath = null)
    {
        $this->nodePath = $nodePath ?: 'node';
    }

    protected function checkFallback($fallback)
    {
        if ($this->isNodeInstalled()) {
            return true;
        }

        if (is_null($fallback)) {
            throw new \ErrorException('Please install node.js or provide a PHP fallback.', 2);
        }

        if (!is_callable($fallback)) {
            throw new \InvalidArgumentException('The fallback provided is not callable.', 1);
        }

        return false;
    }

    protected function shellExec($withNode)
    {
        $preffix = $withNode ? $this->nodePath . ' ' : '';

        return function ($script) use ($preffix) {
            return shell_exec($preffix . $script);
        };
    }

    public function isNodeInstalled()
    {
        $exec = $this->shellExec(true);

        return substr($exec('--version'), 0, 1) === 'v';
    }

    public function exec($script, $fallback = null)
    {
        $exec = $this->checkFallback($fallback)
            ? $this->shellExec(false)
            : $fallback;

        return $exec($script);
    }

    public function nodeExec($script, $fallback = null)
    {
        $exec = $this->checkFallback($fallback)
            ? $this->shellExec(true)
            : $fallback;

        return $exec($script);
    }

    public function execModuleScript($module, $script, $arguments, $fallback = null)
    {
        return $this->nodeExec(
            static::getModuleScript($module, $script) . (empty($arguments) ? '' : ' ' . $arguments),
            $fallback
        );
    }

    public static function getPrefixPath()
    {
        return dirname(dirname(__DIR__));
    }

    public static function getNodeModules()
    {
        return static::getPrefixPath() . DIRECTORY_SEPARATOR . 'node_modules';
    }

    public static function getNodeModule($module)
    {
        return static::getNodeModules() . DIRECTORY_SEPARATOR . $module;
    }

    public static function getModuleScript($module, $script)
    {
        $module = static::getNodeModule($module);
        $path = $module . DIRECTORY_SEPARATOR . $script;
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("The $script was not found in the module path $module.", 3);
        }

        return escapeshellarg(realpath($path));
    }

    public static function install(Event $event)
    {
        $config = $event->getComposer()->getPackage()->getExtra();
        if (!isset($config['npm'])) {
            $event->getIO()->write("Warning: in order to use NodejsPhpFallback, you should add a 'npm' setting in your composer.json");

            return;
        }
        $npm = (array) $config['npm'];
        $packages = '';
        foreach ($npm as $package => $version) {
            if (is_int($package)) {
                $package = $version;
                $version = '*';
            }
            $packages .= ' ' . $package . '@"' . addslashes($version) . '"';
        }

        shell_exec('npm install --prefix ' . escapeshellarg(static::getPrefixPath()) . $packages);
    }
}
