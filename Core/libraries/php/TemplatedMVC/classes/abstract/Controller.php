<?php
abstract class Controller extends ControllerBase {
    protected $pageTitle;
    protected $pageName;
    protected $scripts = array();
    /**
     * @var Cache
     */
    private $cache;

    private $params = array();

    function __construct() {
        if (CACHE_LOC != null && strlen(CACHE_LOC) > 0) {
            $this->cache = new Cache(CACHE_LOC);
        }
        $this->router();
    }

    protected function router() {
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

    protected function outputCache(string $key, int $expiresInSeconds = 120, callable $viewFunc) {
        if (isset($this->cache)) {
            $cachedOutput = $this->cache->GetOutputCache($key);
            if (isset($cachedOutput) && strlen($cachedOutput) > 0) {
                echo $cachedOutput;
            } else {
                $output = $viewFunc();
                // $output = $this->getView($model, $view, $master, $viewData);
                $this->cache->SetOutputCache($key, $expiresInSeconds, $output);
                echo $output;
            }
        } else {
            echo $viewFunc();
        }
    }

    protected function view(object $model = null, string $view = ACTION_NAME, string $master = "_layout", $viewData = null, int $responseCode = HttpStatusCode::OK): void {
        if ($responseCode !== HttpStatusCode::OK) {
            http_response_code($responseCode);
        }
        echo $this->getView($model, $view, $master, $viewData, $responseCode);
    }

    protected function getView(object $model = null, string $view = ACTION_NAME, string $master = "_layout", $viewData = null, int $responseCode = HttpStatusCode::OK): string {
        if ($responseCode !== HttpStatusCode::OK) {
            http_response_code($responseCode);
        }
        $page = new Page($this->pageName, $this->pageTitle, "", $view, join(",", $this->scripts));
        if (strpos($master, '/')) {
            $page->Site = Files::OpenFile($master);
        } else {
            $page->Site = Files::OpenFile(VIEWS_PATH . "/shared/$master.dat");
        }
        $page->HandleSiteIncludes(function ($fileName) {
            return Files::OpenFile($fileName);
        });
        if (strpos($page->Template, '/') === true) {
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
        //Load page specifics
        //Add in the scripts
        if (isset($page->Scripts) === true && count($page->Scripts) > 0) {
            foreach ($page->Scripts as $key => $script) {
                if (strlen($page->SiteVars["Scripts"]) > 0) {
                    $page->SiteVars["Scripts"] .= "\n";
                }
                $page->SiteVars["Scripts"] .= "<script src=\"".$ScriptDir.$script."\" type=\"text/javascript\"></script>";
            }
        }
        //Set the site title
        if (isset($page->Title) === true && strlen($page->Title) > 0) {
            $page->SiteVars["SiteTitle"] = $page->Title;
        }
        //Load the menus
        if (isset($Menus) === true && count($Menus) > 0) {
            foreach ($Menus as $menuId => $menu) {
                $menuTemplate = $page->GetSiteSection($menuId);
                $menuItems = "";
                foreach ($menu->MenuItems as $key => $menuItem) {
                    if (isset($pages[$menuItem->PageId])) {
                        $pageInfo = $pages[$menuItem->PageId];
                        $itemAr = array("Link" => $pageInfo->Link, "Name" => $pageInfo->Name, "Class" => $menu->DefaultClass);
                        if ($menuItem->PageId == $pageId) {
                            $itemAr["Class"] = $menu->ActiveClass;
                        }
                        if ((count($menuItem->AltPageIds) > 0) && in_array($pageId, $menuItem->AltPageIds)) {
                            $itemAr["Class"] = $menu->ActiveClass;
                        }
                        $menuItems .= ArrayReplace($itemAr, $menuTemplate);
                    }
                }
                $page->SetSiteSection($menuId, $menuItems);
            }
        }
        return $page->Show(false);
    }
}
?>