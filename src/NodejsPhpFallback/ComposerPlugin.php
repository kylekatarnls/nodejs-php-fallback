<?php

namespace NodejsPhpFallback;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class ComposerPlugin implements PluginInterface
{
    // public function activate(Composer $composer, IOInterface $io)
    // {
    //     $installer = new TemplateInstaller($io, $composer);
    //     $composer->getInstallationManager()->addInstaller($installer);
    // }

    public static function getSubscribedEvents()
    {
        return array(
            'post-autoload-dump' => 'NodejsPhpFallback\\NodejsPhpFallback::install',
        );
    }
}
