<?php

namespace devpirates\MVC;

use devpirates\MVC\Base\ControllerResponse;
use devpirates\MVC\Interfaces\ILogger;
use devpirates\MVC\Interfaces\IOutputCache;
use devpirates\MVC\Interfaces\IThrottle;
use devpirates\MVC\SessionThrottle;

/**
 * @author nieminen <nieminen432@gmail.com>
 */
class TemplateMVCApp
{
    /**
     * @var \PDO
     */
    public $DB;
    /**
     * Menus array, takes array of array of MenuItem ['menuName' => [MenuItem, MenuItem]]
     *
     * @var array
     */
    public $Menus;
    /**
     * Menus array, takes array of functions to be used as liquid filters ['filterName' => function, 'filterName2' => function]
     *
     * @var array
     */
    public $LiquidFilters;

    /**
     * @var callable
     */
    public $BuildMenusCallback;

    private $_controllerName;
    private $_actionName;
    private $_routeParams;
    private $_area;
    private $_viewDirectory;
    /**
     * Returns the registered throttling implementation.
     * 
     * @var callable
     */
    private $throttleGetter;
    /**
     * returns the registered output cache implementation
     * 
     * @var callable
     */
    private $outputCacheGetter;
    /**
     * @var ?ILogger
     */
    public $logger;

    public function __construct(?string $templateExtension = "haml")
    {
        $this->logger = null;
        define("TEMPLATE_EXTENSION", $templateExtension);
        define('REQUEST_GET', $this->cleanseParams($_GET));
        $postObjJson = file_get_contents("php://input");
        $postArr = [];
        if (empty($postObjJson) === false) {
            $decoded = json_decode($postObjJson, true);
            $postArr = $decoded ? $decoded : [];
        }
        define('REQUEST_POST', $this->cleanseParams(array_merge($_POST, $postArr)));

        $area = isset(REQUEST_GET["Area"]) && !empty(REQUEST_GET["Area"]) ? REQUEST_GET["Area"] : "";
        $controllerName = isset(REQUEST_GET["Controller"]) && !empty(REQUEST_GET["Controller"]) ? REQUEST_GET["Controller"] : "home";
        $viewDirectory = $controllerName;
        if ($controllerName === "404") {
            $controllerName = "fileNotFound";
        }
        $controllerName .= ucfirst($area) . "Controller";
        $actionName = isset(REQUEST_GET["Action"]) && !empty(REQUEST_GET["Action"]) ? REQUEST_GET["Action"] : "index";
        $routeParams = isset(REQUEST_GET["RouteParams"]) && !empty(REQUEST_GET["RouteParams"]) ? REQUEST_GET["RouteParams"] : "";

        $this->_controllerName = ucfirst($controllerName); // unix file systems are case sensitive, must match filename exactly
        $this->_actionName = $actionName;
        $this->_routeParams = $routeParams;
        $this->_area = $area;
        $this->_viewDirectory = $viewDirectory;
        $this->LiquidFilters = array();
        $this->BuildMenusCallback = function ($area): ?array { return null; };
        $this->throttleGetter = function (TemplateMVCApp $app) : ?IThrottle { return new SessionThrottle($app); };
        $this->outputCacheGetter = function (?ILogger $logger) : ?IOutputCache { return null; };
    }

    public function SetLogger(ILogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * This cleans up the get and post parameters to help prevent XSS.
     *
     * @param array $arr
     * @return mixed
     */
    private function cleanseParams(array $arr): mixed
    {
        $params = [];
        foreach ($arr as $key => $value) {
            if (gettype($value) === "string") {
                // real_escape_string doesn't exist on PDO, and won't require it if using prepared statements like you should
                //$params[$key] = ($this->db != null) ? $this->db->real_escape_string(htmlspecialchars($value)) : htmlspecialchars($value);
                $params[$key] = htmlspecialchars($value);
            } else {
                $params[$key] = $value;
            }
        }
        return $params;
    }

    private $paths;
    private $controllerPath;
    /**
     * This function sets up the php autoload register for the mvc app.
     * $paths should be at the very least your controller folder path
     *
     * @param string $controllerPath
     * @param array $paths
     * @return void
     */
    public function Autoload(string $controllerPath, array $paths = null)
    {
        $this->controllerPath = $controllerPath;
        $_paths = array(
            $controllerPath
        );
        if (isset($this->_area) !== null && strlen($this->_area) > 0) {
            $_paths[] = "$controllerPath/" . $this->_area;
        }
        if (isset($paths) && count($paths) > 0) {
            foreach ($paths as $path) {
                $_paths[] = $path;
            }
        }
        if ($_paths !== null && count($_paths) > 0) {
            $this->paths = $_paths;
            spl_autoload_register(function ($class) {
                if (strripos($class, '\\') !== false) {
                    $class = substr($class, strripos($class, '\\') + 1);
                }
                foreach ($this->paths as $path) {
                    $filePath = fileExists("$path/$class.php", false);
                    if ($filePath) {
                        require_once($filePath);
                        break;
                    }
                }
            });
        }
    }

    //potential TODO: implement DB session handler
    // http://phpsec.org/projects/guide/5.html

    private $sessionName;
    /**
     * Set up PDO mysql DB access
     *
     * @param string $dbServer
     * @param string $dbName
     * @param string $dbUser
     * @param string $dbPass
     * @param string $sessionId
     * @return void
     */
    public function Config(string $dbServer, string $dbName, string $dbUser, string $dbPass)
    {
        try {
            $this->DB = new \PDO("mysql:host=$dbServer;dbname=$dbName", $dbUser, $dbPass);
            $this->DB->query('SET NAMES utf8');

            // TODO: Remove for production
            // $this->DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            // echo 'Connection error: ' . $e->getMessage();
            if (isset($this->logger)) {
                $this->logger->Error("TemplateMVCApp::Config", "Connection error: " . $e->getMessage());
            }
        }
    }

    /**
     * Set up session
     *
     * @param string $sessionId
     * @return void
     */
    public function ConfigSession(string $sessionId = "SID")
    {
        try {
            $this->sessionName = $sessionId;
            session_name($this->sessionName);
            session_start();
        } catch (\Exception $e) {
            if (isset($this->logger)) {
                $this->logger->Error("TemplateMVCApp::ConfigSession", "Connection error: " . $e->getMessage());
            }
        }
    }

    /**
     * Register a function to get the throttle implementation.
     * Must return an instance of IThrottle.
     *
     * @param callable $throttleCallback
     * @return void
     */
    public function RegisterThrottleImplementation(callable $throttleCallback) : void
    {
        $this->throttleGetter = $throttleCallback;
    }

    /**
     * Register a function to get the Output Cache implementation.
     * Must return an instance of IOutputCache.
     *
     * @param callable $outputCacheCallback
     * @return void
     */
    public function RegisterOutputCacheImplementation(callable $outputCacheCallback) : void
    {
        $this->outputCacheGetter = $outputCacheCallback;
    }

    /**
     * Start the mvc process
     *
     * @param string $viewsPath
     * @param string $fileNotFoundControllerName
     * @param array $siteData
     * @param array $menus
     * @return void
     */
    public function Start(string $viewsPath, string $fileNotFoundControllerName, array $siteData = null)
    {
        try {
            $this->Menus = array();
            define("VIEWS_PATH", $viewsPath);

            if (isset($siteData) && count($siteData) > 0) {
                define("SITE_DATA", $siteData);
            }
            
            define('AREA', $this->_area);

            if (isset($this->BuildMenusCallback)) {
                $this->Menus = ($this->BuildMenusCallback)(AREA);
            }

            $controllerPath = $this->controllerPath;
            if (AREA != null && strlen(AREA) > 0) {
                $controllerPath .= "/" . AREA;
            }

            $self = $this;
            $fnfControllerFunc = function ($fnfControllerName) use ($self) {
                $this->_actionName = 'index';
                $this->_routeParams = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
                $this->_viewDirectory = 'filenotfound';
                return new $fnfControllerName($self);
            };

            $filePath = fileExists("$controllerPath/" . $this->_controllerName . '.php', false);
            if ($filePath) {
                $controller = new $this->_controllerName($this);
                if (method_exists($controller, $this->_actionName) === false) {
                    $controller = $fnfControllerFunc($fileNotFoundControllerName);
                }
            } else {
                $controller = $fnfControllerFunc($fileNotFoundControllerName);
            }
            define("ACTION_NAME", $this->_actionName);
            define("ROUTE_PARAMS", $this->_routeParams);
            define("CONTROLLER_NAME", $this->_controllerName);
            define('VIEW_DIRECTORY', $this->_viewDirectory);
            $controllerResp = call_user_func_array(array($controller, $this->_actionName), !empty($this->_routeParams) ? explode('/', $this->_routeParams) : []);
            $resp = null;
            if ($controllerResp instanceof ControllerResponse) {
                $resp = $controllerResp;
            } else {
                $resp = new ControllerResponse($controllerResp, 200);
            }
            http_response_code($resp->statusCode);
            echo $resp->output;
        } catch (\Throwable $th) {
            // echo "<pre>";
            // var_dump($th);
            // echo "</pre>";
            // die();
            if (isset($this->logger)) {
                $this->logger->Error("TemplateMVCApp::Start", "Error: " . $th->getMessage());
            }
            http_response_code(HttpStatusCode::INTERNAL_SERVER_ERROR);
        }
    }

    public function GetBaseLiquidFilters(): array
    {
        return array(
            "get_menu_item_class" => function ($menuItem) {
                return preg_match($menuItem->MatchPattern, $_SERVER['REQUEST_URI']) ? $menuItem->ActiveClass : "";
            },
            "fingerprint" => function (string $resourcePath, string $relativePath = "./", string $paramName = 'x') {
                return Files::Fingerprint($resourcePath, $relativePath, $paramName);
            },
            "number" => function ($amount, $decimals = 2) {
                return number_format($amount, $decimals);
            },
            "booltotext" => function (bool $value, string $trueText = "Yes", string $falseText = "No") {
                return $value ? $trueText : $falseText;
            },
            "slugify" => function ($text, string $divider = '-') {
                // replace non letter or digits by divider
                $text = preg_replace('~[^\pL\d]+~u', $divider, $text);
                // transliterate
                $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
                // remove unwanted characters
                $text = preg_replace('~[^-\w]+~', '', $text);
                // trim
                $text = trim($text, $divider);
                // remove duplicate divider
                $text = preg_replace('~-+~', $divider, $text);
                // lowercase
                $text = strtolower($text);

                if (empty($text)) {
                    return 'n-a';
                }

                return $text;
            }
        );
    }

    /**
     * @var IThrottle
     */
    private $throttler = null;

    /**
     * This method returns the throttle implementation to be used as a singleton
     *
     * @return IThrottle
     */
    public function GetThrottler(): IThrottle
    {
        if ($this->throttler === null) {
            $this->throttler = ($this->throttleGetter)($this);
        }
        return $this->throttler;
    }

    /**
     * @var IOutputCache
     */
    private $outputCacher = null;

    /**
     * This method returns the Output Cache implementation to be used as a singleton
     *
     * @return IOutputCache | null
     */
    public function GetOutputCacher(): ?IOutputCache
    {
        if ($this->outputCacher === null) {
            $this->outputCacher = ($this->outputCacheGetter)($this->logger);
        }
        return $this->outputCacher;
    }
}

function fileExists($fileName, $caseSensitive = true)
{
    if (file_exists($fileName)) {
        return $fileName;
    }
    if ($caseSensitive) return false;

    // Handle case insensitive requests            
    $directoryName = dirname($fileName);
    $fileArray = glob($directoryName . '/*', GLOB_NOSORT);
    $fileNameLowerCase = strtolower($fileName);
    foreach ($fileArray as $file) {
        if (strtolower($file) == $fileNameLowerCase) {
            return $file;
        }
    }
    return false;
}
