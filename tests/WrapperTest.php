<?php

namespace NodejsPhpFallbackTest;

use NodejsPhpFallback\NodejsPhpFallback;
use NodejsPhpFallback\Wrapper;

class UpperCaseWrapperWithNode extends Wrapper
{
    public function compile()
    {
        return 'compile:' . $this->getPath('source.upper') . ':' . strtoupper($this->getSource());
    }

    public function fallback()
    {
        return 'fallback:' . $this->getPath('source.upper') . ':' . strtoupper($this->getSource());
    }
}

class UpperCaseWrapperWithoutNode extends UpperCaseWrapperWithNode
{
    public function __construct($file)
    {
        parent::__construct($file);
        $this->node = new NodejsPhpFallback('nowhere');
    }

    public function compile()
    {
    }
}

class NodejsPhpFallbackTest extends TestCase
{
    public function testExecModuleScript()
    {
        $wrapper = new UpperCaseWrapperWithoutNode('foo');

        static::removeDirectory(__DIR__ . '/../node_modules');
        mkdir(__DIR__ . '/../node_modules/foo', 0777, true);
        touch(__DIR__ . '/../node_modules/foo/foo');
        chmod(__DIR__ . '/../node_modules/foo/foo', 0777);
        $withFallback = $wrapper->execModuleScript('foo', 'foo', 'foo', function () {
            return 42;
        });
        $withoutFallback = $wrapper->execModuleScript('foo', 'foo', 'foo');
        static::removeDirectory(__DIR__ . '/../node_modules');

        $this->assertSame(42, $withFallback);
        $this->assertSame(null, $withoutFallback);
    }

    public function testGetPath()
    {
        $wrapper = new UpperCaseWrapperWithNode(__FILE__);
        $this->assertSame(__FILE__, $wrapper->getPath());

        $wrapper = new UpperCaseWrapperWithNode('does/not/exists');
        $this->assertSame(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'source.tmp', $wrapper->getPath());
        $this->assertSame(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bar', $wrapper->getPath('bar'));
    }

    public function testGetSource()
    {
        $wrapper = new UpperCaseWrapperWithNode(__FILE__);
        $this->assertSame(file_get_contents(__FILE__), $wrapper->getSource());

        $wrapper = new UpperCaseWrapperWithNode('does/not/exists');
        $this->assertSame('does/not/exists', $wrapper->getSource());
    }

    public function testGetResult()
    {
        $wrapper = new UpperCaseWrapperWithNode(__FILE__);
        $this->assertSame('compile:' . __FILE__ . ':' . strtoupper(file_get_contents(__FILE__)), $wrapper->getResult());

        $wrapper = new UpperCaseWrapperWithoutNode(__FILE__);
        $this->assertSame('fallback:' . __FILE__ . ':' . strtoupper(file_get_contents(__FILE__)), $wrapper->getResult());
    }

    public function testExec()
    {
        $wrapper = new UpperCaseWrapperWithNode(__FILE__);
        $this->assertSame('compile:' . __FILE__ . ':' . strtoupper(file_get_contents(__FILE__)), $wrapper->exec());

        $wrapper = new UpperCaseWrapperWithoutNode(__FILE__);
        $this->assertSame('fallback:' . __FILE__ . ':' . strtoupper(file_get_contents(__FILE__)), $wrapper->exec());
    }

    public function testWrite()
    {
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test.tmp';

        $wrapper = new UpperCaseWrapperWithNode(__FILE__);
        $wrapper->write($file);
        $text = file_get_contents($file);
        unlink($file);
        $this->assertSame('compile:' . __FILE__ . ':' . strtoupper(file_get_contents(__FILE__)), $text);

        $wrapper = new UpperCaseWrapperWithoutNode(__FILE__);
        $wrapper->write($file);
        $text = file_get_contents($file);
        unlink($file);
        $this->assertSame('fallback:' . __FILE__ . ':' . strtoupper(file_get_contents(__FILE__)), $text);
    }

    public function testToString()
    {
        $wrapper = new UpperCaseWrapperWithNode(__FILE__);
        $this->assertSame('compile:' . __FILE__ . ':' . strtoupper(file_get_contents(__FILE__)), strval($wrapper));

        $wrapper = new UpperCaseWrapperWithoutNode(__FILE__);
        $this->assertSame('fallback:' . __FILE__ . ':' . strtoupper(file_get_contents(__FILE__)), strval($wrapper));
    }
}
