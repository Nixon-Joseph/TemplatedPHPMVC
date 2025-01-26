<?php

namespace devpirates\MVC\Base;

use devpirates\MVC\Interfaces\ILogger;
use devpirates\MVC\Interfaces\LogLevels;

abstract class LoggerBase implements ILogger {
    protected $minLevel = LogLevels::TRACE;

    public function SetMinLevel(int $level)
    {
        $this->minLevel = $level;
    }

    public function Log(string $trace, string $message, int $level)
    {
        if ($level >= $this->minLevel) {
            $this->_Log($trace, $message, $level);
        }
    }

    public function Trace(string $trace, string $message)
    {
        $this->Log($trace, $message, LogLevels::TRACE);
    }

    public function Debug(string $trace, string $message)
    {
        $this->Log($trace, $message, LogLevels::DEBUG);
    }

    public function Info(string $trace, string $message)
    {
        $this->Log($trace, $message, LogLevels::INFO);
    }

    public function Warning(string $trace, string $message)
    {
        $this->Log($trace, $message, LogLevels::WARNING);
    }

    public function Error(string $trace, string $message)
    {
        $this->Log($trace, $message, LogLevels::ERROR);
    }

    abstract protected function _Log(string $trace, string $message, int $level);
}

?>