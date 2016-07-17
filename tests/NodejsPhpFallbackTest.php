<?php

use Composer\Config;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use NodejsPhpFallback\NodejsPhpFallback;

class NodejsPhpFallbackTest extends PHPUnit_Framework_TestCase
{
    public function testInstall()
    {
    	$appDirectory = dirname(__DIR__);
    	$config = new Config(false, $appDirectory);
    	$composer = new Composer();
    	$composer->setConfig($config);
    	$io = new IOInterface();
    	$event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertTrue(is_dir($appDirectory . '/nodes_modules/stylus'));
        $this->assertTrue(is_dir($appDirectory . '/nodes_modules/pug'));
    }

    /**
     * @depends testInstall
     */
    public function testIsNodeInstalled()
    {
        $node = new NodejsPhpFallback(__DIR__ . '/lib/fake-node/node');
        $this->assertTrue($node->isNodeInstalled());

        $node = new NodejsPhpFallback(__DIR__ . '/lib/empty-directory/node');
        $this->assertFalse($node->isNodeInstalled());
    }

    /**
     * @depends testInstall
     */
    public function testExec()
    {
        // prepare
        $stylusFile = sys_get_temp_dir() . '/test.styl';

        // test
        $node = new NodejsPhpFallback();
        file_put_contents($stylusFile, "a\n  color red");
        $css = $node->exec(escapeshellarg(__DIR__ . '/../vendor/stylus/stylus/bin/stylus') . ' --print ' . escapeshellarg($stylusFile));

        // cleanup
        unlink($stylusFile);

        // compare result
        $this->assertSame('a{color:#f00;}', preg_replace('/\s/', '', $css), 'A program such as stylus should return the cli call result.');
    }

    /**
     * @depends testInstall
     */
    public function testNodeExec()
    {
        // prepare
        $pugFile = sys_get_temp_dir() . '/test.pug';

        // test
        $node = new NodejsPhpFallback();
        file_put_contents($pugFile, "h1\n  em Hello");
        $html = $node->nodeExec(escapeshellarg(__DIR__ . '/../vendor/pug/pug-cli/index.js') . ' ' . escapeshellarg($pugFile));

        // cleanup
        unlink($pugFile);

        // compare result
        $this->assertSame('<h1><em>Hello</em></h1>', preg_replace('/\s/', '', $html), 'A program such as pug-cli should return the cli call result.');
    }
}
