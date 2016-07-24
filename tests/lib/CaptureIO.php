<?php

use Composer\IO\NullIO;

class CaptureIO extends NullIO
{
    protected $lastMsg;

    public function write($msg, $newline = true, $verbosity = self::NORMAL)
    {
        $this->lastMsg = $msg;
    }

    public function getLastOutput()
    {
        return $this->lastMsg;
    }
}
