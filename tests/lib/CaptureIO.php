<?php

use Composer\IO\NullIO;

class CaptureIO extends NullIO
{
    protected $lastMsg;
    protected $errored;

    public function write($msg, $newline = true, $verbosity = self::NORMAL)
    {
        $this->lastMsg = $msg;
        $this->errored = false;
    }

    public function writeError($msg, $newline = true, $verbosity = self::NORMAL)
    {
        $this->lastMsg = $msg;
        $this->errored = true;
    }

    public function isErrored()
    {
        return $this->errored;
    }

    public function getLastOutput()
    {
        return $this->lastMsg;
    }
}
