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

    protected function execOrFallback($script, $fallback, $withNode)
    {
        $exec = $this->checkFallback($fallback)
            ? $this->shellExec($withNode)
            : $fallback;

        return call_user_func($exec, $script);
    }

    public function isNodeInstalled()
    {
        $exec = $this->shellExec(true);

        return substr($exec('--version'), 0, 1) === 'v';
    }

    public function exec($script, $fallback = null)
    {
        return $this->execOrFallback($script, $fallback, false);
    }

    public function nodeExec($script, $fallback = null)
    {
        return $this->execOrFallback($script, $fallback, true);
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
        $composer = $event->getComposer();
        $package = $composer->getPackage();
        $dependancies = array_merge(
            array_keys($package->getDevRequires()),
            array_keys($package->getRequires())
        );
        $vendorDir = $composer->getConfig()->get('vendor-dir');
        $config = $package->getExtra();
        $npm = isset($config['npm'])
            ? (array) $config['npm']
            : array();

        foreach ($dependancies as $dependancy) {
            $json = new JsonFile($vendorDir . DIRECTORY_SEPARATOR . $dependancy . DIRECTORY_SEPARATOR . 'composer.json');
            try {
                $dependancyConfig = $json->read();
            } catch (\RuntimeException $e) {
                $dependancyConfig = null;
            }
            if (is_array($dependancyConfig) && isset($dependancyConfig['extra'], $dependancyConfig['extra']['npm'])) {
                $npm = array_merge((array) $dependancyConfig['extra']['npm'], $npm);
            }
        }

        if (!count($npm)) {
            if (!isset($config['npm'])) {
                $event->getIO()->write("Warning: in order to use NodejsPhpFallback, you should add a 'npm' setting in your composer.json");

                return;
            }
            $event->getIO()->write('No packages found.');
        }

        $packages = '';
        foreach ($npm as $package => $version) {
            if (is_int($package)) {
                $package = $version;
                $version = '*';
            }
            $install = $package . '@"' . addslashes($version) . '"';
            $event->getIO()->write('Package founded added to be installed with npm: ' . $install);
            $packages .= ' ' . $install;
        }

        shell_exec('npm install --prefix ' . escapeshellarg(static::getPrefixPath()) . $packages);
    }
}
