<?php

use Composer\Script\Event;
use NodejsPhpFallback\ComposerPlugin;
use NodejsPhpFallbackTest\TestCase;

class ComposerPluginTest extends TestCase
{
    protected static $deleteAfterTest = ['node_modules', 'etc', 'jade', 'jade.cmd', 'stylus', 'stylus.cmd'];

    public function testPluginActivate()
    {
        $composer = $this->emulateComposer([
            'toto/toto' => '{"extra":{"npm":"stylus"}}',
        ]);
        $io = new CaptureIO();
        $event = new Event('install', $composer, $io);
        $plugin = new ComposerPlugin();
        $plugin->activate($composer, $io);
        $events = ComposerPlugin::getSubscribedEvents();
        $this->assertInternalType('array', $events);
        $this->assertInternalType('array', $events['post-autoload-dump']);
        $this->assertInternalType('array', $events['post-autoload-dump'][0]);
        $method = $events['post-autoload-dump'][0][0];
        $plugin->$method($event);

        $this->assertDirectoryExists(static::appDirectory().'/node_modules/stylus');
        static::removeTestDirectories();
    }
}
