<?php  namespace devpirates\MVC\Base;

use zz\Html\HTMLMinify;

abstract class Controller extends \devpirates\MVC\Base\ControllerBase {
    protected $pageTitle;
    protected $pageName;
    protected $scripts = array();
    protected $styles = array();

    private $params = array();

    public abstract function Index();

    /**
     * Handles output caching controller action
     *
     * @param string $key
     * @param callable $viewFunc
     * @param integer $expiresInSeconds
     * @return void
     */
    protected function outputCache(string $key, callable $viewFunc, int $expiresInSeconds = 120) {
        if (isset($this->cache)) {
            $cachedOutput = $this->cache->GetOutputCache($key);
            if (isset($cachedOutput) && strlen($cachedOutput) > 0) {
                echo $cachedOutput;
            } else {
                $output = $viewFunc();
                $this->cache->SetOutputCache($key, $expiresInSeconds, $output);
                echo $output;
            }
        } else {
            echo $viewFunc();
        }
    }

    /**
     * Display view for controller action
     * If array is passed in as $model, it should be ['key' => 'value'] format
     *
     * @param object|array|null $model
     * @param string $view
     * @param string $master
     * @param array|null $viewData
     * @param boolean $minify
     * @return void
     */
    protected function view($model = null, string $view = ACTION_NAME, string $master = "_layout", ?array $viewData = null, bool $minify = true): void {
        echo $this->getView($model, $view, $master, $viewData, $minify);
    }

    /**
     * Get view for controller action
     * If array is passed in as $model, it should be ['key' => 'value'] format
     *
     * @param object|array|null $model
     * @param string $view
     * @param string $master
     * @param array|null $viewData
     * @param boolean $minify
     * @return string
     */
    protected function getView($model = null, string $view = ACTION_NAME, string $master = "_layout", ?array $viewData = null, bool $minify = true): string {
        \Liquid\Liquid::set('INCLUDE_ALLOW_EXT', true);

        $viewPath = strlen(AREA) ? (VIEWS_PATH . "/areas/" . AREA) : VIEWS_PATH;
        $template = new \Liquid\Template($viewPath);

        /**
         * @global \devpirates\MVC\TemplateMVCApp
         */
        global $app;

        $allFilters = array_merge($app->GetBaseLiquidFilters(), isset($app->LiquidFilters) ? $app->LiquidFilters : array());
        if (isset($allFilters) && count($allFilters)) {
            foreach ($allFilters as $key => $value) {
                $template->registerFilter($key, $value);
            }
        }

        if (strpos($view, '/') !== false) {
            $template->parse(\devpirates\MVC\Files::OpenFile($view));
        } else {
            $folderName = VIEW_DIRECTORY;
            $template->parse(\devpirates\MVC\Files::OpenFile($viewPath . "/$folderName/$view." . TEMPLATE_EXTENSION)); 
        }
        $pageContent = $template->render(array('model' => $model, 'view_data' => $viewData));

        if (strpos($master, '/') !== false) {
            $template->parse(\devpirates\MVC\Files::OpenFile($master));
        } else {
            $template->parse(\devpirates\MVC\Files::OpenFile($viewPath . "/shared/$master." . TEMPLATE_EXTENSION));
        }
        
        $output = $template->render(array(
            'content' => $pageContent,
            'siteData' => SITE_DATA,
            'viewData' => $viewData,
            'menus' => $app->Menus,
            'scripts' => $this->scripts,
            'styles' => $this->styles
        ));
        if ($minify) {
            $output = HTMLMinify::minify($output);
        }
        return $output;
    }
}
?>