<?php

namespace devpirates\MVC\Base;

use devpirates\MVC\HttpStatusCode;
use devpirates\MVC\TemplateMVCApp;

abstract class ControllerBase
{
    /**
     * @var IOutputCache | null
     */
    protected $cache;
    /**
     * @var TemplateMVCApp
     */
    protected $app;

    public function __construct(TemplateMVCApp $app)
    {
        $this->cache = $app->GetOutputCacher();
        $this->app = $app;
    }

    public function redirect(string $path): void
    {
        header("Location: $path");
        exit;
    }

    abstract protected function ok(mixed $output = null) : ControllerResponse;

    abstract protected function notFound(mixed $output = null) : ControllerResponse;

    abstract protected function badRequest(mixed $output = null) : ControllerResponse;

    abstract protected function unauthorized(mixed $output = null) : ControllerResponse;

    abstract protected function forbidden(mixed $output = null) : ControllerResponse;

    abstract protected function internalServerError(mixed $output = null) : ControllerResponse;

    abstract protected function response(mixed $output = null, int $statusCode) : ControllerResponse;

    /**
     * Handles output caching controller action
     *
     * @param string $key
     * @param callable $viewFunc
     * @param integer $expiresInSeconds
     * @return mixed
     */
    protected function outputCache(string $key, callable $viewFunc, int $expiresInSeconds = 120, int $statusCode = 200): ControllerResponse
    {
        if (!empty($this->cache)) {
            $cachedOutput = $this->cache->GetOutputCache($key);
            if (isset($cachedOutput) && strlen($cachedOutput) > 0) {
                return new ControllerResponse($cachedOutput, $statusCode);
            } else {
                $output = $viewFunc();
                $this->cache->SetOutputCache($key, $expiresInSeconds, $output);
                return new ControllerResponse($output, $statusCode);
            }
        } else {
            return $viewFunc();
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
     * @param mixed|null $additionalInfo
     * @return ControllerResponse
     */
    protected function throttle(string $throttleName, int $timesPer, int $minutes, callable $method, $callableParams = null, mixed $additionalInfo = null): ControllerResponse
    {
        $shouldThrottle = false;
        // get throttle implementation from app
        $throttler = $this->app->GetThrottler();
        $shouldThrottle = $throttler->shouldThrottle($throttleName, $timesPer, $minutes, $additionalInfo);
        if ($shouldThrottle === true) {
            return $this->response("Too many requests, please wait a bit and try again.", HttpStatusCode::TOO_MANY_REQUESTS);
        } else {
            if (isset($callableParams)) {
                return $method($callableParams);
            } else {
                return $method();
            }
        }
    }
}

class ControllerResponse
{
    /*
    * @var string | null
    */
    public $output;
    /*
    * @var integer
    */
    public $statusCode;

    public function __construct(?string $output, $statusCode = 200)
    {
        $this->output = $output;
        $this->statusCode = $statusCode;
    }
}
