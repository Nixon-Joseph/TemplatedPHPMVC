<?php
/**
 * @author nieminen <nieminen432@gmail.com>
 */
class App {
    private $config = [];
    public $db;
    function __construct () {
        $controllerName = "homeController";
        $viewDirectory = "home";
        if (isset($_GET["Controller"]) && !empty($_GET["Controller"])) {
            $controllerName = $_GET["Controller"];
            $viewDirectory = $controllerName;
            if ($controllerName === "404") {
                $controllerName = "fileNotFound";
            }
            $controllerName .= "Controller";
        }
        $actionName = "index";
        if (isset($_GET["Action"]) && !empty($_GET["Action"])) {
            $actionName = $_GET["Action"];
        }
        $routeParams = "";
        if (isset($_GET["RouteParams"]) && !empty($_GET["RouteParams"])) {
            $routeParams = $_GET["RouteParams"];
        }
        define("CONTROLLER_NAME", $controllerName);
        define("ACTION_NAME", $actionName);
        define("ROUTE_PARAMS", $routeParams);
        define('VIEW_DIRECTORY', ucfirst($viewDirectory));
        define("VIEW_DATA", []);
    }
    function config () {
        $this->require('./Core/Config/session.php');
        $this->require('./Core/Config/database.php');
        $this->require("./Core/Config/constants.php");
        try {
            $this->db = new PDO(
                'mysql:host=' . $this->config['database']['hostname'] . ';dbname=' . $this->config['database']['dbname'],
                $this->config['database']['username'], 
                $this->config['database']['password']
            );
            $this->db->query('SET NAMES utf8');
            $this->db->query('SET CHARACTER_SET utf8_unicode_ci');
            
            // TODO: Remove for production
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Connection error: ' . $e->getMessage();
        }
    }
    function autoload () {
        spl_autoload_register(function ($class) {
            $class = strtolower($class);
            if (file_exists('./Core/Classes/' . $class . '.php')) {
                require_once './Core/Classes/' . $class . '.php';
            } else if (file_exists('./Core/Helpers/' . $class . '.php')) {
                require_once './Core/Helpers/' . $class . '.php';
            }
        });
    }
    function require ($path) {
        require $path;
    }
    function start () {
        session_name($this->config['sessionName']);
        session_start();

        if (file_exists('./App/Controllers/' . CONTROLLER_NAME . '.php')) {
            $this->require('./App/Controllers/' . CONTROLLER_NAME . '.php');
            $controller = CONTROLLER_NAME;
            $c = new $controller();
        } else {
            $this->require('./App/Controllers/fileNotFoundController.php');
            $c = new FileNotFoundController();
        }
    }
}
?>