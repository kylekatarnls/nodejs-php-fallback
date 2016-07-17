<?php

namespace NodejsPhpFallback;

use Composer\Script\Event;

class NodejsPhpFallback
{
    public function isNodeInstalled()
    {
        return substr($this->nodeExec('--version'), 0, 1) === 'v';
    }

    public function exec($script, $fallback = null)
    {
        if (!$this->isNodeInstalled()) {
            if (is_null($fallback)) {
                throw new \ErrorException('Please install node.js or provide a PHP fallback.', 2);
            }

            if (!is_callable($fallback)) {
                throw new \InvalidArgumentException('The fallback provided is not callable.', 1);
            }

            return $fallback($package, $script);
        }

        return shell_exec($script);
    }

    public function nodeExec($cmd)
    {
        return shell_exec('node ' . $cmd);
    }

    public static function install(Event $e)
    {
        exit('ici');
        $config = $e->getComposer()->getConfig();
        if (!$config->has('npm')) {
            echo "Warning: in order to use NodejsPhpFallback, you should add a 'npm' setting in your composer.json";
            return;
        }
        $packages = '';
        foreach ($config->get('npm') as $package => $version) {
            $packages .= ' ' . $package . '@"' . addslashes($version) . '"';
        }

        return shell_exec('npm install --prefix ' . escapeshellarg(__DIR__ . '/../../nodes_modules') . $packages);
    }
}
