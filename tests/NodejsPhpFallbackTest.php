<?php

use Composer\IO\NullIO;
use Composer\Script\Event;
use NodejsPhpFallback\NodejsPhpFallback;
use NodejsPhpFallbackTest\TestCase;

class NodejsPhpFallbackTest extends TestCase
{
    protected static $deleteAfterTest = ['node_modules', 'etc', 'jade', 'jade.cmd', 'stylus', 'stylus.cmd'];

    protected function setUp(): void
    {
        parent::setUp();
        NodejsPhpFallback::forgetConfirmRemindedChoice();
    }

    public static function setUpBeforeClass(): void
    {
        static::removeTestDirectories();
    }

    public static function tearDownAfterClass(): void
    {
        static::removeTestDirectories();
    }

    public function testNodeVersion()
    {
        $node = new NodejsPhpFallback();
        $version = ltrim($node->nodeExec('--version'), 'v');

        self::assertTrue(
            version_compare($version, '7') >= 0,
            'Unit tests should be run with node 7. node --version: '.$version
        );
    }

    public function testStringInstall()
    {
        $composer = $this->emulateComposer([
            'toto/toto' => '{"extra":{"npm":"stylus"}}',
        ]);
        $io = new NullIO();
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/stylus'));
        $this->assertSame(realpath(static::appDirectory()), NodejsPhpFallback::getPrefixPath());
        $this->assertSame(realpath(static::appDirectory().'/node_modules'), NodejsPhpFallback::getNodeModules());
        $this->assertSame(realpath(static::appDirectory().'/node_modules/stylus'), NodejsPhpFallback::getNodeModule('stylus'));
        $this->assertSame(escapeshellarg(realpath(static::appDirectory().'/node_modules/stylus/bin/stylus')), NodejsPhpFallback::getModuleScript('stylus', 'bin/stylus'));
        NodejsPhpFallback::setModulePath('stylus', 'custom/stylus');
        $this->assertSame('custom/stylus', NodejsPhpFallback::getNodeModule('stylus'));
        NodejsPhpFallback::setModulePath('stylus', null);
        static::removeTestDirectories();
    }

    public function testArrayInstall()
    {
        $composer = $this->emulateComposer([
            'toto/toto' => '{"extra":{"npm":["stylus","pug-cli"]}}',
        ]);
        $io = new CaptureIO();
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertSame('Packages installed.', $io->getLastOutput());
        $this->assertFalse($io->isErrored());
        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/stylus'));
        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/pug-cli'));
        static::removeTestDirectories();
    }

    public function testInstallFailure()
    {
        $composer = $this->emulateComposer([
            'toto/toto' => '{"extra":{"npm":["i-m-pretty-sure-this-plugin-does-not-exist"]}}',
        ]);
        $io = new CaptureIO();
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::setMaxInstallRetry(2);
        NodejsPhpFallback::install($event);

        $this->assertSame('Installation failed after 2 tries.', $io->getLastOutput());
        $this->assertTrue($io->isErrored());
        static::removeTestDirectories();
    }

    public function testInstallDependancies()
    {
        $composer = $this->emulateComposer([
            'foo/bar'   => '{"extra":{"npm":"stylus"}}',
            'baz/boo'   => '{"extra":{"npm":["pug-cli"]}}',
            'not/found' => false,
        ]);
        $io = new NullIO();
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/stylus'));
        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/pug-cli'));
        static::removeTestDirectories();
    }

    public function testInstallConfirmNonInteractive()
    {
        $composer = $this->emulateComposer([
            'x/y' => '{"extra":{"npm":{"stylus":"^0.54","pug-cli":"*"},"npm-confirm":{"stylus":"reason"}}}',
        ]);
        $io = new CaptureIO();
        $io->setInteractive(false);
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/stylus'));
        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/pug-cli'));
        static::removeTestDirectories();
    }

    public function testInstallConfirmYesAnswer()
    {
        $composer = $this->emulateComposer([
            'x/y' => '{"extra":{"npm":{"stylus":"^0.54","pug-cli":"*"},"npm-confirm":{"stylus":"reason"}}}',
        ]);
        $io = new CaptureIO();
        $io->setInteractive(true);
        $io->setAnswer(true);
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/stylus'));
        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/pug-cli'));
        static::removeTestDirectories();
    }

    public function testInstallConfirmNoAnswer()
    {
        $composer = $this->emulateComposer([
            'x/y' => '{"extra":{"npm":{"stylus":"^0.54","pug-cli":"*"},"npm-confirm":{"stylus":"reason"}}}',
        ]);
        $io = new CaptureIO();
        $io->setInteractive(true);
        $io->setAnswer(false);
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertFalse(is_dir(static::appDirectory().'/node_modules/stylus'));
        $this->assertFalse(NodejsPhpFallback::isInstalledPackage('stylus'));
        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/pug-cli'));
        $this->assertTrue(NodejsPhpFallback::isInstalledPackage('pug-cli'));
        static::removeTestDirectories();
    }

    public function testInitialAnswer()
    {
        $composer = $this->emulateComposer([
            'x/y' => '{"extra":{"npm":{"stylus":"^0.54","pug-cli":"*"},"npm-confirm":{"stylus":"reason"}}}',
        ]);
        $io = new CaptureIO();
        $io->setInteractive(true);
        $io->setInitialAnswer('Y');
        $io->setAnswer(false);
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/stylus'));
        $this->assertTrue(NodejsPhpFallback::isInstalledPackage('stylus'));
        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/pug-cli'));
        $this->assertTrue(NodejsPhpFallback::isInstalledPackage('pug-cli'));
        static::removeTestDirectories();

        $composer = $this->emulateComposer([
            'x/y' => '{"extra":{"npm":{"stylus":"^0.54","pug-cli":"*"},"npm-confirm":{"stylus":"reason"}}}',
        ]);
        $io = new CaptureIO();
        $io->setInteractive(true);
        $io->setInitialAnswer('N');
        $io->setAnswer(false);
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/stylus'));
        $this->assertTrue(NodejsPhpFallback::isInstalledPackage('stylus'));
        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/pug-cli'));
        $this->assertTrue(NodejsPhpFallback::isInstalledPackage('pug-cli'));
        static::removeTestDirectories();

        NodejsPhpFallback::forgetConfirmRemindedChoice();

        $composer = $this->emulateComposer([
            'x/y' => '{"extra":{"npm":{"stylus":"^0.54","pug-cli":"*"},"npm-confirm":{"stylus":"reason"}}}',
        ]);
        $io = new CaptureIO();
        $io->setInteractive(true);
        $io->setInitialAnswer('N');
        $io->setAnswer(false);
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertFalse(is_dir(static::appDirectory().'/node_modules/stylus'));
        $this->assertFalse(NodejsPhpFallback::isInstalledPackage('stylus'));
        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/pug-cli'));
        $this->assertTrue(NodejsPhpFallback::isInstalledPackage('pug-cli'));
        static::removeTestDirectories();

        $composer = $this->emulateComposer([
            'x/y' => '{"extra":{"npm":{"stylus":"^0.54","pug-cli":"*"},"npm-confirm":{"stylus":"reason"}}}',
        ]);
        $io = new CaptureIO();
        $io->setInteractive(true);
        $io->setInitialAnswer('Y');
        $io->setAnswer(false);
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertFalse(is_dir(static::appDirectory().'/node_modules/stylus'));
        $this->assertFalse(NodejsPhpFallback::isInstalledPackage('stylus'));
        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/pug-cli'));
        $this->assertTrue(NodejsPhpFallback::isInstalledPackage('pug-cli'));
        static::removeTestDirectories();
    }

    public function testInstallPackages()
    {
        $this->assertTrue(NodejsPhpFallback::installPackages([]));

        NodejsPhpFallback::installPackages([
            'stylus'  => '^0.54',
            'pug-cli' => '*',
        ]);

        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/stylus'));
        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/pug-cli'));
        static::removeTestDirectories();
    }

    public function testNpmConfirmInExtra()
    {
        $composer = $this->emulateComposer([
            'x/y' => '{"extra":{"npm":{"stylus":"^0.54","pug-cli":"*"}}}',
        ]);
        $composer->getPackage()->setExtra([
            'npm-confirm' => [
                'pug-cli' => 'For pug',
            ],
        ]);
        $io = new CaptureIO();
        $io->setInteractive(true);
        $io->setAnswer(false);
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/stylus'));
        $this->assertFalse(is_dir(static::appDirectory().'/node_modules/pug-cli'));
        static::removeTestDirectories();
    }

    public function testInstall()
    {
        $composer = $this->emulateComposer([
            'x/y' => '{"extra":{"npm":{"stylus":"^0.54","pug-cli":"*"}}}',
        ]);
        $io = new NullIO();
        $event = new Event('install', $composer, $io);
        NodejsPhpFallback::install($event);

        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/stylus'));
        $this->assertTrue(is_dir(static::appDirectory().'/node_modules/pug-cli'));
    }

    /**
     * @depends testInstall
     */
    public function testIsNodeInstalled()
    {
        chmod(__DIR__.'/lib/fake-node/node', 0777);
        $node = new NodejsPhpFallback(__DIR__.'/lib/fake-node/node');
        $this->assertTrue($node->isNodeInstalled());

        $node = new NodejsPhpFallback(__DIR__.'/lib/empty-directory/node');
        $this->assertFalse($node->isNodeInstalled());
    }

    /**
     * @depends testInstall
     */
    public function testNodeExecStylus()
    {
        // prepare
        $stylusFile = sys_get_temp_dir().'/test.styl';

        // test
        $node = new NodejsPhpFallback();
        file_put_contents($stylusFile, "a\n  color red");
        $css = $node->nodeExec(escapeshellarg(static::appDirectory().'/node_modules/stylus/bin/stylus').' --print '.escapeshellarg($stylusFile));

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
        $stylusFile = sys_get_temp_dir().'/test.styl';

        // test
        $node = new NodejsPhpFallback();
        file_put_contents($stylusFile, "a\n  color red");
        $css = $node->execModuleScript('stylus', 'bin/stylus', '--print '.escapeshellarg($stylusFile));

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
        $pugFile = sys_get_temp_dir().'/test.pug';
        $htmlFile = sys_get_temp_dir().'/test.html';

        // test
        $node = new NodejsPhpFallback();
        file_put_contents($pugFile, "h1\n  em Hello");
        chdir(static::appDirectory().'/node_modules/pug-cli');
        $node->nodeExec(escapeshellarg('.'.DIRECTORY_SEPARATOR.'index.js').' < '.escapeshellarg($pugFile).' > '.escapeshellarg($htmlFile));
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
        chdir(static::appDirectory().'/tests/lib');
        chmod('simple', 0777);
        $simple = $node->exec(escapeshellarg('.'.DIRECTORY_SEPARATOR.'simple'), function () {
            return 'fail';
        });

        $this->assertSame('foo-bar', trim($simple), 'A cli program should be available if node is installed.');
    }

    /**
     * @depends testInstall
     */
    public function testNodeExecWithoutNode()
    {
        $node = new NodejsPhpFallback(__DIR__.'/lib/empty-directory/node');
        chdir(static::appDirectory().'/tests/lib');
        $simple = $node->exec(escapeshellarg('.'.DIRECTORY_SEPARATOR.'simple'), function () {
            return 'fail';
        });

        $this->assertSame('fail', trim($simple), 'A cli program should not be available if node is not installed.');
    }

    /**
     * @depends testInstall
     */
    public function testModuleExecWithoutNode()
    {
        $node = new NodejsPhpFallback(__DIR__.'/lib/empty-directory/node');
        chdir(static::appDirectory().'/tests/lib');
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
        $node = new NodejsPhpFallback(__DIR__.'/lib/empty-directory/node');
        chdir(static::appDirectory().'/tests/lib');
        $simple = $node->exec(escapeshellarg('.'.DIRECTORY_SEPARATOR.'simple'), 42);
    }

    /**
     * @depends testInstall
     */
    public function testBadConfig()
    {
        $composer = $this->emulateComposer([
            'x/y' => '{"extra":{"no-npm":{"foo":"^1.0"}}}',
        ]);
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
        $composer = $this->emulateComposer([
            'x/y' => '{"extra":{"npm":{}}}',
        ]);
        $composer->getPackage()->setExtra([
            'npm' => [],
        ]);
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
        $node = new NodejsPhpFallback(__DIR__.'/lib/empty-directory/node');
        $node->nodeExec('foo', 'bar');
    }

    /**
     * @depends testInstall
     * @expectedException \ErrorException
     * @expectedExceptionCode 2
     */
    public function testNoFallback()
    {
        $node = new NodejsPhpFallback(__DIR__.'/lib/empty-directory/node');
        $node->nodeExec('foo');
    }

    /**
     * @depends testInstall
     */
    public function testFallback()
    {
        $output = null;
        $node = new NodejsPhpFallback(__DIR__.'/lib/empty-directory/node');
        $return = $node->nodeExec('foo', function ($script) use (&$output) {
            $output = $script;

            return 'bar';
        });

        $this->assertSame('foo', $output, 'Fallback must be called if the node is not installed, and the input script should be passed to it.');
        $this->assertSame('bar', $return, 'Fallback returned value must be sent throught nodeExec.');
    }

    public function testSetNodePath()
    {
        $node = new NodejsPhpFallback('foo');

        $this->assertSame('foo', $node->getNodePath());

        $copy = $node->setNodePath('bar');

        $this->assertSame('bar', $node->getNodePath());
        $this->assertSame($copy, $node);
    }

    public function testEnvAnswer()
    {
        putenv('NODEJS_PHP_FALLBACK_ANSWER=y');
        $method = new ReflectionMethod('NodejsPhpFallback\\NodejsPhpFallback', 'getGlobalInstallChoice');
        $method->setAccessible(true);
        $answer = $method->invoke(null, new NullIO(), '');
        putenv('NODEJS_PHP_FALLBACK_ANSWER');

        $this->assertSame('y', $answer);
    }
}
