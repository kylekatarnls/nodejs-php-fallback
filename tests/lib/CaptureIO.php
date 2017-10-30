<?php

use Composer\IO\NullIO;

class CaptureIO extends NullIO
{
    protected $lastMsg;
    protected $errored;
    protected $interactive;

    public function isInteractive()
    {
        return $this->interactive;
    }

    public function setInteractive($interactive)
    {
        $this->interactive = $interactive;
    }

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
