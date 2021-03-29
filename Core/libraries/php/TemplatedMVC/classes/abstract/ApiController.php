<?php namespace devpirates\MVC\Base;
abstract class ApiController extends \devpirates\MVC\Base\ControllerBase {
    /**
     * Print jsonified output of any object passed in.
     * Prints 'null' if $data param is null
     * 
     * @param string|object $data
     * @param integer $responseCode
     * @return void
     */
    protected function respond($data): void {
        header('Content-Type: application/json');
        if (isset($data) && $data !== null) {
            echo json_encode($data);
        } else {
            echo 'null';
        }
    }

    /**
     * This method throttles the passed in work to allow for rate limiting to help stop DDOS or brute force attacks
     *
     * @param string $throttleName
     * @param integer $timesPer
     * @param integer $minutes
     * @param callable $method
     * @param any|null $callableParams
     * @return void
     */
    protected function throttle(string $throttleName, int $timesPer, int $minutes, callable $method, ?any $callableParams = null): void {
        $throttle;
        if (isset($_SESSION["Throttle-$throttleName"])) {
            $throttle = json_decode($_SESSION["Throttle-$throttleName"]);
        } else {
            $throttle = new ThrottleDescriptor();
        }
        $okayToRun = false;
        if (isset($throttle->FirstAccess)) {
            if (Time() - $throttle->FirstAccess > ($minutes * 60)) {
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
        if ($okayToRun === true) {
            if (isset($callableParams)) {
                $method($callableParams);
            } else {
                $method();
            }
        } else {
            $this->setResponseStatus(HttpStatusCode::TOO_MANY_REQUESTS);
            echo "Too many requests, please wait a bit and try again.";
        }
    }
}

class ThrottleDescriptor {
    public $AccessCount;
    public $FirstAccess;

    public function __construct() {
        $this->AccessCount = 0;
    }
}
?>