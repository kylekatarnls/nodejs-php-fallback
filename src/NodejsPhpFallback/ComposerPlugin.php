<?php

namespace NodejsPhpFallback;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PreFileDownloadEvent;
use Composer\Script\Event;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        // $installer = new TemplateInstaller($io, $composer);
        // $composer->getInstallationManager()->addInstaller($installer);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'post-autoload-dump' => array(
                array('onAutoloadDump', 0),
            ),
        );
    }

    public function onAutoloadDump(Event $event)
    {
        NodejsPhpFallback::install($event);
    }
}
