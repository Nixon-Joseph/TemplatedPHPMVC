<?php

namespace devpirates\MVC\Base;

use devpirates\MVC\TemplateMVCApp;

/**
 * Base controller class
 * All controllers should extend this class
 * Controller classes should be named as [ControllerName]Controller
 * For example: HomeController
 * 
 * Controller constructor now requires TemplateMVCApp object to eliminate global variable use
 * @package devpirates\MVC\Base
 */
abstract class Controller extends ControllerBase
{
    protected $pageTitle;
    protected $pageName;
    protected $scripts = array();
    protected $styles = array();

    public function __construct(TemplateMVCApp $app)
    {
        parent::__construct($app);
    }

    public abstract function Index();

    /**
     * Display partial view for controller action
     * If array is passed in as $model, it should be ['key' => 'value'] format
     * 
     * @param object|array|null $model
     * @param string $view
     * @param array|null $viewData
     * @param boolean $minify
     * @return ControllerResponse
     */
    protected function partial(string $view, $model = null, ?array $viewData = null, bool $minify = true): ControllerResponse
    {
        return $this->ok($this->getView($model, $view, null, $viewData, $minify));
    }


    /**
     * Get partial view for controller action
     * If array is passed in as $model, it should be ['key' => 'value'] format
     * 
     * @param object|array|null $model
     * @param string $view
     * @param array|null $viewData
     * @param boolean $minify
     * @return string
     */
    protected function getPartial(string $view, $model = null, ?array $viewData = null, bool $minify = true): string
    {
        return $this->getView($model, $view, null, $viewData, $minify);
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
     * @return ControllerResponse
     */
    protected function view($model = null, string $view = ACTION_NAME, string $master = "_layout", ?array $viewData = null, bool $minify = true): ControllerResponse
    {
        return $this->ok($this->getView($model, $view, $master, $viewData, $minify));
    }

    /**
     * Get view for controller action
     * If array is passed in as $model, it should be ['key' => 'value'] format
     *
     * @param object|array|null $model
     * @param string $view
     * @param string|null $master
     * @param array|null $viewData
     * @param boolean $minify
     * @return string
     */
    protected function getView($model = null, string $view = ACTION_NAME, $master = "_layout", ?array $viewData = null): string
    {
        \Liquid\Liquid::set('INCLUDE_ALLOW_EXT', true);

        $viewPath = strlen(AREA) ? (VIEWS_PATH . "/areas/" . AREA) : VIEWS_PATH;
        $template = new \Liquid\Template($viewPath);

        $allFilters = array_merge($this->app->GetBaseLiquidFilters(), isset($this->app->LiquidFilters) ? $this->app->LiquidFilters : array());
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

        if (isset($master) && strlen($master) > 0) {
            if (strpos($master, '/') !== false) {
                $template->parse(\devpirates\MVC\Files::OpenFile($master));
            } else {
                $template->parse(\devpirates\MVC\Files::OpenFile($viewPath . "/shared/$master." . TEMPLATE_EXTENSION));
            }
            $output = $template->render(array(
                'content' => $pageContent,
                'siteData' => SITE_DATA,
                'viewData' => $viewData,
                'menus' => $this->app->Menus,
                'scripts' => $this->scripts,
                'styles' => $this->styles
            ));
        } else {
            $output = $pageContent;
        }

        return $output;
    }
}
