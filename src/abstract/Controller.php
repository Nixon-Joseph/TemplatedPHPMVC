<?php  namespace devpirates\MVC\Base;
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
     * @param integer $expiresInSeconds
     * @param callable $viewFunc
     * @return void
     */
    protected function outputCache(string $key, int $expiresInSeconds = 120, callable $viewFunc) {
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
     * @return void
     */
    protected function view($model = null, string $view = ACTION_NAME, string $master = "_layout", ?array $viewData = null): void {
        echo $this->getView($model, $view, $master, $viewData);
    }

    /**
     * Get view for controller action
     * If array is passed in as $model, it should be ['key' => 'value'] format
     *
     * @param object|array|null $model
     * @param string $view
     * @param string $master
     * @param array|null $viewData
     * @return string
     */
    protected function getView($model = null, string $view = ACTION_NAME, string $master = "_layout", ?array $viewData = null): string {
        
        \Liquid\Liquid::set('INCLUDE_ALLOW_EXT', true);

        $template = new \Liquid\Template(VIEWS_PATH);

        if (strpos($view, '/') !== false) {
            $template->parse(\devpirates\MVC\Files::OpenFile($view));
        } else {
            $folderName = VIEW_DIRECTORY;
            $template->parse(\devpirates\MVC\Files::OpenFile(VIEWS_PATH . "/$folderName/$view." . TEMPLATE_EXTENSION));
        }
        $pageContent = $template->render(array('model' => $model, 'view_data' => $viewData));

        if (strpos($master, '/') !== false) {
            $template->parse(\devpirates\MVC\Files::OpenFile($master));
        } else {
            $template->parse(\devpirates\MVC\Files::OpenFile(VIEWS_PATH . "/shared/$master." . TEMPLATE_EXTENSION));
        }

        global $app;
        return $template->render(array(
            'content' => $pageContent,
            'siteData' => SITE_DATA,
            'viewData' => $viewData,
            'menus' => $app->Menus,
            'scripts' => $this->scripts,
            'styles' => $this->styles
        ));
    }
}
?>