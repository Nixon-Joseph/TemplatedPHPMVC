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
                call_user_func_array(array($this, ACTION_NAME), explode('/', !empty(ROUTE_PARAMS) ? ROUTE_PARAMS : ""));
            } else {
                header('Location: /404/notfound/' . ACTION_NAME);
            }
        } else {
            header('Location: /404');
        }
    }
    public abstract function index();
    public function view (string $view = ACTION_NAME) {
        require "./Core/Classes/templates.php";
        $this->displayPage(new oPage($this->pageName, $this->pageTitle, "", $view, join(",", $this->scripts)));
    }

    private function displayPage(oPage $page) {
        require "./Core/Classes/files.php";
        $page->Site = OpenFile("./App/Views/Shared/_layout.dat");
        $page->Content = OpenFile("./App/Views/Home/$page->Template.dat");

        $page->SiteVars["SiteTitle"] = Constants::GET_SITE_NAME();
        $page->SiteVars["SiteName"] = Constants::GET_SITE_NAME();
        $page->SiteVars["Scripts"] = "";
        $page->SiteVars["SiteSubtitle"] = Constants::GET_SITE_SUBTITLE();
        $page->SiteVars["SiteDescription"] =Constants::GET_SITE_DESCRIPTION();

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