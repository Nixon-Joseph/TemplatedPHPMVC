<?php namespace devpirates\MVC\Base;
abstract class ControllerBase {
    /**
     * @var Cache
     */
    protected $cache;

    public function __construct() {
        if (CACHE_LOC != null && strlen(CACHE_LOC) > 0) {
            $this->cache = new Cache(CACHE_LOC);
        }
    }

    /**
     * Sets the http response code for this request
     *
     * @param integer $responseCode
     * @return void
     */
    protected function setResponseStatus(int $responseCode): void {
        if ($responseCode !== HttpStatusCode::OK) {
            http_response_code($responseCode);
        }
    }

    public function redirect(string $path): void {
        header("Location: $path");
        exit;
    }
}
?>