<?php

namespace devpirates\MVC\Interfaces;

interface ILogger
{
    public function SetMinLevel(int $level);
    public function Log(string $trace, string $message, int $level);
    public function Trace(string $trace, string $message);
    public function Debug(string $trace, string $message);
    public function Info(string $trace, string $message);
    public function Warning(string $trace, string $message);
    public function Error(string $trace, string $message);
}

class LogLevels
{
    public const TRACE = 1;
    public const DEBUG = 2;
    public const INFO = 3;
    public const WARNING = 4;
    public const ERROR = 5;
}

?>