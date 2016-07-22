<?php

namespace NodejsPhpFallback;

use Composer\Script\Event;

class NodejsPhpFallback
{
    protected $nodePath;

    public function __construct($nodePath = null)
    {
        $this->nodePath = isset($nodePath) ? $nodePath : 'node';
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

    public static function install(Event $event)
    {
        $config = $event->getComposer()->getConfig();
        if (!$config->has('npm')) {
            $event->getIO()->write("Warning: in order to use NodejsPhpFallback, you should add a 'npm' setting in your composer.json");

            return;
        }
        $packages = '';
        foreach ($config->get('npm') as $package => $version) {
            $packages .= ' ' . $package . '@"' . addslashes($version) . '"';
        }

        shell_exec('npm install --prefix ' . escapeshellarg(__DIR__ . '/../..') . $packages);
    }
}
