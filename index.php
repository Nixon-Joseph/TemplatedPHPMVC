<?php
/**
 * @author nieminen <nieminen432@gmail.com>
 */
$start = microtime(true);
$templatedMVCPath = __DIR__ . './core/libraries/php/TemplatedMVC';
require "$templatedMVCPath/TemplateMVCApp.php";
$app = new TemplateMVCApp("./app/cache");
$app->Autoload($templatedMVCPath, "./app/controllers", array("./app/models", "./app/helpers"));
require "../private/mvc_db_creds.php";
$app->Config($dbServer, $dbName, $dbUser, $dbPass);
unset ($dbServer, $dbName, $dbUser, $dbPass);

require_once "./app/classes/Constants.php";

$siteData = array();
$siteData["SiteTitle"] = Constants::SITE_NAME;
$siteData["SiteName"] = Constants::SITE_NAME;
$siteData["Scripts"] = "";
$siteData["SiteSubtitle"] = Constants::SITE_SUBTITLE;
$siteData["CopyYear"] = date("Y");
$siteData["SiteAddress"] = Constants::SITE_ADDRESS;
$siteData["SiteDescription"] = Constants::SITE_DESCRIPTION;
$siteData["PageTitle"] = Constants::SITE_NAME;

$app->Start("./app/views", "FileNotFoundController", $siteData);
echo 'Render took ' . number_format(microtime(true) - $start, 3) . ' seconds.';
?>