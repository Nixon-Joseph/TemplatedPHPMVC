<?php
/**
 * @author nieminen <nieminen432@gmail.com>
 */
class TemplateMVCApp {
    private $config = [];
    public $db;
    function __construct (?string $cacheLoc = null) {
        define('REQUEST_GET', $this->cleanseParams($_GET));
        define('REQUEST_POST', $this->cleanseParams($_POST));

        define("CACHE_LOC", $cacheLoc);

        $controllerName = "homeController";
        $viewDirectory = "home";
        if (isset(REQUEST_GET["Controller"]) && !empty(REQUEST_GET["Controller"])) {
            $controllerName = REQUEST_GET["Controller"];
            $viewDirectory = $controllerName;
            if ($controllerName === "404") {
                $controllerName = "fileNotFound";
            }
            $controllerName .= "Controller";
        }
        $actionName = "index";
        if (isset(REQUEST_GET["Action"]) && !empty(REQUEST_GET["Action"])) {
            $actionName = REQUEST_GET["Action"];
        }
        $routeParams = "";
        if (isset(REQUEST_GET["RouteParams"]) && !empty(REQUEST_GET["RouteParams"])) {
            $routeParams = REQUEST_GET["RouteParams"];
        }
        define("CONTROLLER_NAME", $controllerName);
        define("ACTION_NAME", $actionName);
        define("ROUTE_PARAMS", $routeParams);
        define('VIEW_DIRECTORY', ucfirst($viewDirectory));
    }
    private function cleanseParams(array $arr) {
        $params = [];
        foreach ($arr as $key => $value) {
            if (gettype($value) === "string") {
                $params[$key] = ($db != null) ? $db->real_escape_string(htmlspecialchars($value)) : htmlspecialchars($value);
            } else {
                $params[$key] = $value;
            }
        }
        return $params;
    }
    private $sessionName;
    public function Config(string $dbServer, string $dbName, string $dbUser, string $dbPass, string $sessionId = "SID") {
        try {
            $this->sessionName = $sessionId;
            $this->db = new PDO("mysql:host=$dbServer;dbname=$dbName", $dbUser, $dbPass);
            $this->db->query('SET NAMES utf8');
            $this->db->query('SET CHARACTER_SET utf8_unicode_ci');
            
            // TODO: Remove for production
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Connection error: ' . $e->getMessage();
        }
    }
    /**
     * Autoload
     * 
     * $paths should be at the very least your controller folder path
     * 
     * @param array $paths 
     * @return void
     */
    private $paths;
    private $controllerPath;
    public function Autoload(string $libPath, string $controllerPath, array $paths = null) {
        $this->controllerPath = $controllerPath;
        $_paths = array("$libPath/classes", "$libPath/classes/abstract", "$libPath/includes/jsonmapper", $controllerPath);
        array_merge($_paths, $paths);
        if ($_paths !== null && count($_paths) > 0) {
            $this->paths = $_paths;
            spl_autoload_register(function ($class) {
                foreach ($this->paths as $path) {
                    if (file_exists("$path/$class.php")) {
                        require_once("$path/$class.php");
                    }
                }
            });
        }
    }
    public function Start(string $viewsPath, string $fileNotFoundControllerName, $siteData = null) {
        define("VIEWS_PATH", $viewsPath);

        if (isset($siteData) && count($siteData) > 0) {
            define("SITE_DATA", $siteData);
        }

        session_name($this->sessionName);
        session_start();

        if (file_exists("$this->controllerPath/" . CONTROLLER_NAME . '.php')) {
            require "$this->controllerPath/" . CONTROLLER_NAME . '.php';
            $controller = CONTROLLER_NAME;
            $c = new $controller();
        } else {
            require "$this->controllerPath/$fileNotFoundControllerName.php";
            $c = new $fileNotFoundControllerName();
        }
    }
}
?>