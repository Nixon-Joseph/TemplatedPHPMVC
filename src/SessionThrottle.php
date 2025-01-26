<?php

namespace devpirates\MVC;

use devpirates\MVC\Base\ThrottleBase;

class SessionThrottle extends ThrottleBase
{
    public function __construct(TemplateMVCApp $app) { 
        parent::__construct($app);
    }

    public function shouldThrottle(string $throttleName, int $timesPer, int $minutes, mixed $additionalInfo = null): bool
    {
        if (isset($_SESSION["Throttle-$throttleName"])) {
            $throttle = json_decode($_SESSION["Throttle-$throttleName"]);
        } else {
            $throttle = new SessionThrottleDescriptor();
        }
        $okayToRun = false;
        if (isset($throttle->FirstAccess)) {
            if (time() - $throttle->FirstAccess > ($minutes * 60)) {
                $okayToRun = true;
                $throttle->FirstAccess = time();
                $throttle->AccessCount = 0;
            } else if ($throttle->AccessCount < $timesPer) {
                $okayToRun = true;
            }
        } else {
            $okayToRun = true;
            $throttle->FirstAccess = time();
        }
        $throttle->AccessCount++;
        $_SESSION["Throttle-$throttleName"] = json_encode($throttle);
        return !$okayToRun;
    }
}

class SessionThrottleDescriptor
{
    public $AccessCount;
    public $FirstAccess;

    public function __construct()
    {
        $this->AccessCount = 0;
    }
}
?>