<?php

namespace devpirates\MVC\Base;

use devpirates\MVC\Interfaces\IThrottle;
use devpirates\MVC\TemplateMVCApp;

abstract class ThrottleBase implements IThrottle
{
    protected readonly TemplateMVCApp $app;

    public function __construct(TemplateMVCApp $app) {
        $this->app = $app;
    }

    abstract public function shouldThrottle(string $throttleName, int $timesPer, int $minutes, mixed $additionalInfo = null): bool;
}

?>