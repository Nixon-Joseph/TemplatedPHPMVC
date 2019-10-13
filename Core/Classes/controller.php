<?php
abstract class Controller {
    protected $pageTitle;
    protected $pageName;
    protected $folderName;
    protected $scripts = [];

    private $params = [];
    function __construct () {
        $this->router();
    }
    private function router () {
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
    public abstract function index();
    public function view(string $view = ACTION_NAME, string $master = "_layout", mixed $model = null) {
        require "./Core/Classes/templates.php";
        require "./Core/Classes/files.php";

        $page = new oPage($this->pageName, $this->pageTitle, "", $view, join(",", $this->scripts));
        if (strpos($master, '/')) {
            $page->Site = OpenFile("$master");
        } else {
            $page->Site = OpenFile("./App/Views/Shared/$master.dat");
        }
        $page->HandleSiteIncludes(function ($fileName) {
            return OpenFile($fileName);
        });
        if (strpos($page->Template, '/') === true) {
            $page->Content = OpenFile($page->Template);
        } else {
            $folderName = VIEW_DIRECTORY;
            $page->Content = OpenFile("./App/Views/$folderName/$page->Template.dat");
        }
        $page->HandlePageIncludes(function ($fileName) {
            return OpenFile($fileName);
        });

        $page->SiteVars["SiteTitle"] = Constants::SITE_NAME;
        $page->SiteVars["SiteName"] = Constants::SITE_NAME;
        $page->SiteVars["Scripts"] = "";
        $page->SiteVars["SiteSubtitle"] = Constants::SITE_SUBTITLE;
        $page->SiteVars["CopyYear"] = date("Y");
        $page->SiteVars["SiteAddress"] = Constants::SITE_ADDRESS;
        $page->SiteVars["SiteDescription"] = Constants::SITE_DESCRIPTION;
        if (isset($page->Title) === true && strlen($page->Title) > 0) {
            $page->SiteVars["PageTitle"] = $page->Title;
        } else {
            $page->SiteVars["PageTitle"] = Constants::SITE_NAME;
        }

        //Setup the optional site variables
        if (VIEW_DATA != null && count(VIEW_DATA) > 0) {
            $page->SiteVars = array_merge($page->SiteVars, VIEW_DATA);
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
        $page->Show();
    }
}
?>