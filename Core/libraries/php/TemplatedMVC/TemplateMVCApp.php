<?php
/**
 * @author nieminen <nieminen432@gmail.com>
 */
class TemplateMVCApp
{
    private $config = [];
    /**
     * @var PDO
     */
    public $DB;
    /**
     * OAuth2 server
     *
     * @var Server
     */
    public $OAuthServer;
    /**
     * Menus array, takes array of array of MenuItem ['menuName' => [MenuItem, MenuItem]]
     *
     * @var array
     */
    public $Menus;

    private $_controllerName;
    private $_actionName;
    private $_routeParams;
    private $_area;
    private $_viewDirectory;

    public function __construct(?string $cacheLoc = null)
    {
        define('REQUEST_GET', $this->cleanseParams($_GET));
        define('REQUEST_POST', $this->cleanseParams($_POST));

        define("CACHE_LOC", $cacheLoc);

        $area = isset(REQUEST_GET["Area"]) && !empty(REQUEST_GET["Area"]) ? REQUEST_GET["Area"] : "";
        $controllerName = isset(REQUEST_GET["Controller"]) && !empty(REQUEST_GET["Controller"]) ? REQUEST_GET["Controller"] : "home";
        $viewDirectory = "home";
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
        $this->_viewDirectory = (strlen($area) > 0 ? "$viewDirectory/areas/$area" : $viewDirectory);
    }

    /**
     * This cleans up the get and post parameters to help prevent XSS.
     *
     * @param array $arr
     * @return void
     */
    private function cleanseParams(array $arr)
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
     * @param string $libPath
     * @param string $controllerPath
     * @param array $paths
     * @return void
     */
    public function Autoload(string $libPath, string $controllerPath, array $paths = null)
    {
        $this->controllerPath = $controllerPath;
        $_paths = array(
            $controllerPath,
            "$libPath/classes",
            "$libPath/classes/abstract",
            "$libPath/includes/JsonMapper",
            "$libPath/includes/GoogleAuthenticator",
            "$libPath/includes/Emogrifier",
            "$libPath/includes/Emogrifier/HtmlProcessor",
            "$libPath/includes/Emogrifier/Utilities",
            "$libPath/includes/Emogrifier/CssSelector",
            "$libPath/includes/Emogrifier/CssSelector/Exception",
            "$libPath/includes/Emogrifier/CssSelector/Node",
            "$libPath/includes/Emogrifier/CssSelector/Parser",
            "$libPath/includes/Emogrifier/CssSelector/Parser/Handler",
            "$libPath/includes/Emogrifier/CssSelector/Parser/Shortcut",
            "$libPath/includes/Emogrifier/CssSelector/Parser/Tokenizer",
            "$libPath/includes/Emogrifier/CssSelector/XPath",
            "$libPath/includes/Emogrifier/CssSelector/XPath/Extension"
        );
        if (isset($this->this->_area) !== null && strlen($this->_area) > 0) {
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
                    if (file_exists("$path/$class.php")) {
                        require_once ("$path/$class.php");
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
    public function Config(string $dbServer, string $dbName, string $dbUser, string $dbPass, string $sessionId = "SID")
    {
        try {
            $this->sessionName = $sessionId;
            $this->DB = new PDO("mysql:host=$dbServer;dbname=$dbName", $dbUser, $dbPass);
            $this->DB->query('SET NAMES utf8');
            $this->DB->query('SET CHARACTER_SET utf8_unicode_ci');

            // TODO: Remove for production
            $this->DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Connection error: ' . $e->getMessage();
        }
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
    public function Start(string $viewsPath, string $fileNotFoundControllerName, array $siteData = null, array $menus = null)
    {
        try {
            $this->Menus = $menus;
            define("VIEWS_PATH", $viewsPath);
    
            if (isset($siteData) && count($siteData) > 0) {
                define("SITE_DATA", $siteData);
            }
    
            session_name($this->sessionName);
            session_start();
            define('AREA', $this->_area);
    
            $controllerPath = $this->controllerPath;
            if (AREA != null && strlen(AREA) > 0) {
                $controllerPath .= "/" . AREA;
            }
    
            $fnfControllerFunc = function ($fnfControllerName) {
                $this->_actionName = 'index';
                $this->_routeParams = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
                $this->_viewDirectory = 'filenotfound';
                return new $fnfControllerName();
            };
    
            $controller;
            if (file_exists("$controllerPath/" . $this->_controllerName . '.php') === true) {
                $controller = new $this->_controllerName();
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
            call_user_func_array(array($controller, $this->_actionName), !empty($this->_routeParams) ? explode('/', $this->_routeParams) : []);
        } catch (\Throwable $th) {
            http_response_code(HttpStatusCode::INTERNAL_SERVER_ERROR);
        }
    }
}
