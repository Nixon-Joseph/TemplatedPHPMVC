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
     * @param mixed|null $callableParams
     * @return void
     */
    protected function throttle(string $throttleName, int $timesPer, int $minutes, callable $method, $callableParams = null): void {
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
            $this->setResponseStatus(\devpirates\MVC\HttpStatusCode::TOO_MANY_REQUESTS);
            echo "Too many requests, please wait a bit and try again.";
        }
    }

    /**
     * This method ends the current client request, but allows your code to continue executing.
     * Use this to kick off a 'threaded' process where you don't want the client to wait for a single response
     *
     * Source: https://gist.github.com/bubba-h57/32593b2b970366d24be7
     * 
     * @param string $body
     * @param integer $responseCode
     * @param integer $minutes
     * @param callable $method
     * @param mixed|null $callableParams
     * @return void
     */
    protected function closeConnection($data, $responseCode = "200"): void {
        // Cause we are clever and don't want the rest of the script to be bound by a timeout.
        // Set to zero so no time limit is imposed from here on out.
        set_time_limit(0);

        // Close the current session
        @session_write_close();
    
        // Client disconnect should NOT abort our script execution
        ignore_user_abort(true);
    
        // Clean (erase) the output buffer and turn off output buffering
        // in case there was anything up in there to begin with.
        ob_end_clean();
    
        // Turn on output buffering, because ... we just turned it off ...
        // if it was on.
        ob_start();
    
        header('Content-Type: application/json');
        if (isset($data) && $data !== null) {
            echo json_encode($data);
        } else {
            echo 'null';
        }
    
        // Return the length of the output buffer
        $size = ob_get_length();
    
        // send headers to tell the browser to close the connection
        // remember, the headers must be called prior to any actual
        // input being sent via our flush(es) below.
        header("Connection: close\r\n");
        header("Content-Encoding: none\r\n");
        header("Content-Length: $size");
    
        // Set the HTTP response code
        // this is only available in PHP 5.4.0 or greater
        http_response_code($responseCode);
    
        // Flush (send) the output buffer and turn off output buffering
        ob_end_flush();
    
        // Flush (send) the output buffer
        // This looks like overkill, but trust me. I know, you really don't need this
        // unless you do need it, in which case, you will be glad you had it!
        @ob_flush();
    
        // Flush system output buffer
        // I know, more over kill looking stuff, but this
        // Flushes the system write buffers of PHP and whatever backend PHP is using
        // (CGI, a web server, etc). This attempts to push current output all the way
        // to the browser with a few caveats.
        flush();
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