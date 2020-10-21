<?php  namespace devpirates\MVC\Base;
abstract class Controller extends ControllerBase {
    protected $pageTitle;
    protected $pageName;
    protected $scripts = array();

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
        $page = new Page($this->pageName, $this->pageTitle, "", $view, implode(",", $this->scripts));
        if (strpos($master, '/') !== false) {
            $page->Site = Files::OpenFile($master);
        } else {
            $page->Site = Files::OpenFile(VIEWS_PATH . "/shared/$master.dat");
        }
        $page->HandleSiteIncludes(function ($fileName) {
            return Files::OpenFile($fileName);
        });
        if (strpos($page->Template, '/') !== false) {
            $page->Content = Files::OpenFile($page->Template);
        } else {
            $folderName = VIEW_DIRECTORY;
            $page->Content = Files::OpenFile(VIEWS_PATH . "/$folderName/$page->Template.dat");
        }
        $page->HandleModel($model);
        $page->HandlePageIncludes(function ($fileName) {
            return Files::OpenFile($fileName);
        });

        if (isset($page->Title) === true && strlen($page->Title) > 0) {
            $page->SiteVars["PageTitle"] = $page->Title;
        } else {
            $page->SiteVars["PageTitle"] = Constants::SITE_NAME;
        }

        //Setup the optional site variables
        if (SITE_DATA != null) {
            $page->SiteVars = array_merge($page->SiteVars, SITE_DATA);
        }
        if (isset($viewData) && count($viewData) > 0) {
            $page->ContentVars = array_merge($page->ContentVars, $viewData);
        }
        //Set the site title
        if (isset($page->Title) === true && strlen($page->Title) > 0) {
            $page->SiteVars["SiteTitle"] = $page->Title;
        }
        global $app;
        $page->HandleMenus($app->Menus);
        return $page->Show(false);
    }
}
?>