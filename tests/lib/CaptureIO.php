<?php

use Composer\IO\NullIO;

class CaptureIO extends NullIO
{
    protected $lastMsg;
    protected $errored;
    protected $interactive;
    protected $answer;
    protected $initialAnswer;
    protected $answerAsException;

    public function ask($question, $default = null)
    {
        if ($this->answerAsException) {
            throw new Exception('Cannot ask');
        }

        return $this->initialAnswer;
    }

    public function askConfirmation($question, $default = true)
    {
        return $this->answer;
    }

    public function isInteractive()
    {
        return $this->interactive;
    }

    public function setInteractive($interactive)
    {
        $this->interactive = $interactive;
    }

    public function setAnswer($answer)
    {
        $this->answer = $answer;
    }

    public function throwExceptionOnAsk()
    {
        $this->answerAsException = true;
    }

    public function answerOnAsk()
    {
        $this->answerAsException = false;
    }

    public function setInitialAnswer($answer)
    {
        $this->initialAnswer = $answer;
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
