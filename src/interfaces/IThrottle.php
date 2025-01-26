<?php

namespace devpirates\MVC\Interfaces;

use devpirates\MVC\Base\ControllerResponse;

interface IThrottle
{
    /**
     * This method throttles the passed in work to allow for rate limiting to help stop DDOS or brute force attacks
     *
     * @param string $throttleName
     * @param integer $timesPer
     * @param integer $minutes
     * @param mixed|null $additionalInfo
     * @return void
     */
    public function shouldThrottle(string $throttleName, int $timesPer, int $minutes, mixed $additionalInfo = null): bool;
}
?>