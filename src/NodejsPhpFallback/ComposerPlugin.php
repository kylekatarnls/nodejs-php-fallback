<?php

namespace NodejsPhpFallback;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
    }

    public static function getSubscribedEvents()
    {
        return [
            'post-autoload-dump' => [
                ['onAutoloadDump', 0],
            ],
        ];
    }

    public function onAutoloadDump(Event $event)
    {
        NodejsPhpFallback::install($event);
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // TODO: Implement uninstall() method.
    }
}
