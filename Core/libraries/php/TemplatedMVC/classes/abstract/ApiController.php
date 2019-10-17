<?php
abstract class ApiController extends ControllerBase {
    /**
     * @var Cache
     */
    private $cache;

    public function __construct() {
        if (CACHE_LOC != null && strlen(CACHE_LOC) > 0) {
            $this->cache = new Cache(CACHE_LOC);
        }
        $this->router();
    }

    protected function router(): void {
        if (empty(ACTION_NAME) === false) {
            if (method_exists($this, ACTION_NAME) === true) {
                call_user_func_array(array($this, ACTION_NAME), !empty(ROUTE_PARAMS) ? explode('/',  ROUTE_PARAMS) : []);
            } else {
                header('Location: /404/notfound/' . ACTION_NAME);
            }
        } else {
            header('Location: /404');
        }
    }

    /**
     * Print jsonified output of any object passed in.
     * Prints 'null' if $data param is null
     * 
     * @param string|object $data
     * @param integer $responseCode
     * @return void
     */
    protected function respond($data, int $responseCode = HttpStatusCode::OK): void {
        if ($responseCode !== 200) {
            http_response_code($responseCode);
        }
        if (isset($data) && $data !== null) {
            echo json_encode($data);
        } else {
            echo 'null';
        }
    }
}
?>