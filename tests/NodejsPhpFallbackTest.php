<?php

use Composer\Config;
use Composer\IO\NullIO;
use Composer\Script\Event;
use NodejsPhpFallback\NodejsPhpFallback;
use NodejsPhpFallbackTest\TestCase;

class NodejsPhpFallbackTest extends TestCase
{
    protected static $deleteAfterTest = array('node_modules', 'etc', 'jade', 'jade.cmd', 'stylus', 'stylus.cmd');

    public static function setUpBeforeClass()
    {
        static::removeTestDirectories();
    }

    public static function tearDownAfterClass()
    {
        static::removeTestDirectories();
    }

    public function testStringInstall()
    {
        $composer = $this->emulateComposer(array(
            'toto/toto' => '{"extra":{"npm":"stylus"}}',
        ));
        $io = new NullIO();
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertTrue(is_dir(static::appDirectory() . '/node_modules/stylus'));
        $this->assertSame(realpath(static::appDirectory()), NodejsPhpFallback::getPrefixPath());
        $this->assertSame(realpath(static::appDirectory() . '/node_modules'), NodejsPhpFallback::getNodeModules());
        $this->assertSame(realpath(static::appDirectory() . '/node_modules/stylus'), NodejsPhpFallback::getNodeModule('stylus'));
        $this->assertSame(escapeshellarg(realpath(static::appDirectory() . '/node_modules/stylus/bin/stylus')), NodejsPhpFallback::getModuleScript('stylus', 'bin/stylus'));
        static::removeTestDirectories();
    }

    public function testArrayInstall()
    {
        $composer = $this->emulateComposer(array(
            'toto/toto' => '{"extra":{"npm":["stylus","pug-cli"]}}',
        ));
        $io = new NullIO();
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertTrue(is_dir(static::appDirectory() . '/node_modules/stylus'));
        $this->assertTrue(is_dir(static::appDirectory() . '/node_modules/pug-cli'));
        static::removeTestDirectories();
    }

    public function testInstallDependancies()
    {
        $composer = $this->emulateComposer(array(
            'foo/bar'   => '{"extra":{"npm":"stylus"}}',
            'baz/boo'   => '{"extra":{"npm":["pug-cli"]}}',
            'not/found' => false,
        ));
        $io = new NullIO();
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertTrue(is_dir(static::appDirectory() . '/node_modules/stylus'));
        $this->assertTrue(is_dir(static::appDirectory() . '/node_modules/pug-cli'));
        static::removeTestDirectories();
    }

    public function testInstall()
    {
        $composer = $this->emulateComposer(array(
            'x/y' => '{"extra":{"npm":{"stylus":"^0.54","pug-cli":"*"}}}',
        ));
        $io = new NullIO();
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertTrue(is_dir(static::appDirectory() . '/node_modules/stylus'));
        $this->assertTrue(is_dir(static::appDirectory() . '/node_modules/pug-cli'));
    }

    /**
     * @depends testInstall
     */
    public function testIsNodeInstalled()
    {
        chmod(__DIR__ . '/lib/fake-node/node', 0777);
        $node = new NodejsPhpFallback(__DIR__ . '/lib/fake-node/node');
        $this->assertTrue($node->isNodeInstalled());

        $node = new NodejsPhpFallback(__DIR__ . '/lib/empty-directory/node');
        $this->assertFalse($node->isNodeInstalled());
    }

    /**
     * @depends testInstall
     */
    public function testNodeExecStylus()
    {
        // prepare
        $stylusFile = sys_get_temp_dir() . '/test.styl';

        // test
        $node = new NodejsPhpFallback();
        file_put_contents($stylusFile, "a\n  color red");
        $css = $node->nodeExec(escapeshellarg(static::appDirectory() . '/node_modules/stylus/bin/stylus') . ' --print ' . escapeshellarg($stylusFile));

        // cleanup
        unlink($stylusFile);

        // compare result
        $this->assertSame('a{color:#f00;}', preg_replace('/\s/', '', $css), 'A program such as stylus should return the cli call result.');
    }

    /**
     * @depends testInstall
     */
    public function testModuleExecStylus()
    {
        // prepare
        $stylusFile = sys_get_temp_dir() . '/test.styl';

        // test
        $node = new NodejsPhpFallback();
        file_put_contents($stylusFile, "a\n  color red");
        $css = $node->execModuleScript('stylus', 'bin/stylus', '--print ' . escapeshellarg($stylusFile));

        // cleanup
        unlink($stylusFile);

        // compare result
        $this->assertSame('a{color:#f00;}', preg_replace('/\s/', '', $css), 'A program such as stylus should return the cli call result.');
    }

    /**
     * @depends testInstall
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 3
     */
    public function testModuleExecStylusWithMissingScript()
    {
        // test
        $node = new NodejsPhpFallback();
        $node->execModuleScript('stylus', 'bin/i-do-not-exists', '--print foo/bar');
    }

    /**
     * @depends testInstall
     */
    public function testNodeExecPug()
    {
        // prepare
        $pugFile = sys_get_temp_dir() . '/test.pug';
        $htmlFile = sys_get_temp_dir() . '/test.html';

        // test
        $node = new NodejsPhpFallback();
        file_put_contents($pugFile, "h1\n  em Hello");
        chdir(static::appDirectory() . '/node_modules/pug-cli');
        $node->nodeExec(escapeshellarg('.' . DIRECTORY_SEPARATOR . 'index.js') . ' < ' . escapeshellarg($pugFile) . ' > ' . escapeshellarg($htmlFile));
        $html = file_get_contents($htmlFile);

        // cleanup
        unlink($pugFile);

        // compare result
        $this->assertSame('<h1><em>Hello</em></h1>', preg_replace('/\s/', '', $html), 'A program such as pug should return the cli call result.');
    }

    /**
     * @depends testInstall
     */
    public function testExec()
    {
        $node = new NodejsPhpFallback();
        chdir(static::appDirectory() . '/tests/lib');
        chmod('simple', 0777);
        $simple = $node->exec(escapeshellarg('.' . DIRECTORY_SEPARATOR . 'simple'), function () {
            return 'fail';
        });

        $this->assertSame('foo-bar', trim($simple), 'A cli program should be available if node is installed.');
    }

    /**
     * @depends testInstall
     */
    public function testNodeExecWithoutNode()
    {
        $node = new NodejsPhpFallback(__DIR__ . '/lib/empty-directory/node');
        chdir(static::appDirectory() . '/tests/lib');
        $simple = $node->exec(escapeshellarg('.' . DIRECTORY_SEPARATOR . 'simple'), function () {
            return 'fail';
        });

        $this->assertSame('fail', trim($simple), 'A cli program should not be available if node is not installed.');
    }

    /**
     * @depends testInstall
     */
    public function testModuleExecWithoutNode()
    {
        $node = new NodejsPhpFallback(__DIR__ . '/lib/empty-directory/node');
        chdir(static::appDirectory() . '/tests/lib');
        $simple = $node->execModuleScript('stylus', 'bin/stylus', '--print foo/bar.styl', function () {
            return 'fail';
        });

        $this->assertSame('fail', trim($simple), 'A cli program should not be available if node is not installed.');
    }

    /**
     * @depends testInstall
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1
     */
    public function testExecWithoutNodeNorGoodFallback()
    {
        $node = new NodejsPhpFallback(__DIR__ . '/lib/empty-directory/node');
        chdir(static::appDirectory() . '/tests/lib');
        $simple = $node->exec(escapeshellarg('.' . DIRECTORY_SEPARATOR . 'simple'), 42);
    }

    /**
     * @depends testInstall
     */
    public function testBadConfig()
    {
        $composer = $this->emulateComposer(array(
            'x/y' => '{"extra":{"no-npm":{"foo":"^1.0"}}}',
        ));
        $io = new CaptureIO();
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        // compare result
        $this->assertSame(0, strpos($io->getLastOutput(), 'Warning:'), 'If the npm config is missing a warning should be raised.');
    }

    /**
     * @depends testInstall
     */
    public function testEmptyConfig()
    {
        $composer = $this->emulateComposer(array(
            'x/y' => '{"extra":{"npm":{}}}',
        ));
        $composer->getPackage()->setExtra(array(
            'npm' => array(),
        ));
        $io = new CaptureIO();
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        // compare result
        $this->assertSame('No packages found.', $io->getLastOutput(), 'If the npm config is empty a message should be displayed.');
    }

    /**
     * @depends testInstall
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1
     */
    public function testNonCallableFallback()
    {
        $node = new NodejsPhpFallback(__DIR__ . '/lib/empty-directory/node');
        $node->nodeExec('foo', 'bar');
    }

    /**
     * @depends testInstall
     * @expectedException \ErrorException
     * @expectedExceptionCode 2
     */
    public function testNoFallback()
    {
        $node = new NodejsPhpFallback(__DIR__ . '/lib/empty-directory/node');
        $node->nodeExec('foo');
    }

    /**
     * @depends testInstall
     */
    public function testFallback()
    {
        $output = null;
        $node = new NodejsPhpFallback(__DIR__ . '/lib/empty-directory/node');
        $return = $node->nodeExec('foo', function ($script) use (&$output) {
            $output = $script;

            return 'bar';
        });

        $this->assertSame('foo', $output, 'Fallback must be called if the node is not installed, and the input script should be passed to it.');
        $this->assertSame('bar', $return, 'Fallback returned value must be sent throught nodeExec.');
    }
}
