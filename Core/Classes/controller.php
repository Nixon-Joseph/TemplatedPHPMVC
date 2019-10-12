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

        $Page->SiteVars["SiteTitle"] = Constants::GET_SITE_NAME();
        $Page->SiteVars["SiteName"] = Constants::GET_SITE_NAME();
        $Page->SiteVars["Scripts"] = "";
        $Page->SiteVars["SiteSubtitle"] = Constants::GET_SITE_SUBTITLE();
        $Page->SiteVars["SiteDescription"] =Constants::GET_SITE_DESCRIPTION();

        //Setup the optional site variables
        if (VIEW_DATA != null && count(VIEW_DATA) > 0) {
            $Page->SiteVars = array_merge($Page->SiteVars, VIEW_DATA);
        }
        //Load page specifics
        //Add in the scripts
        if (isset($Page->Scripts) === true && count($Page->Scripts) > 0) {
            foreach ($Page->Scripts as $key => $script) {
                if (strlen($Page->SiteVars["Scripts"]) > 0) {
                    $Page->SiteVars["Scripts"] .= "\n";
                }
                $Page->SiteVars["Scripts"] .= "<script src=\"".$ScriptDir.$script."\" type=\"text/javascript\"></script>";
            }
        }
        //Set the site title
        if (isset($Page->Title) === true && strlen($Page->Title) > 0) {
            $Page->SiteVars["SiteTitle"] = $Page->Title;
        }
        //Load the menus
        if (isset($Menus) === true && count($Menus) > 0) {
            foreach ($Menus as $menuId => $menu) {
                $menuTemplate = $Page->GetSiteSection($menuId);
                $menuItems = "";
                foreach ($menu->MenuItems as $key => $menuItem) {
                    if (isset($Pages[$menuItem->PageId])) {
                        $pageInfo = $Pages[$menuItem->PageId];
                        $itemAr = array("Link" => $pageInfo->Link, "Name" => $pageInfo->Name, "Class" => $menu->DefaultClass);
                        if ($menuItem->PageId == $PageId) {
                            $itemAr["Class"] = $menu->ActiveClass;
                        }
                        if ((count($menuItem->AltPageIds) > 0) && in_array($PageId, $menuItem->AltPageIds)) {
                            $itemAr["Class"] = $menu->ActiveClass;
                        }
                        $menuItems .= ArrayReplace($itemAr, $menuTemplate);
                    }
                }
                $Page->SetSiteSection($menuId, $menuItems);
            }
        }
        $page->Show();
    }
}
?>