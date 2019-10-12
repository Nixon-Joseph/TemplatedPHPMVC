<?php

//

class oMenu
{
	public $ActiveClass;
	public $DefaultClass;
	public $MenuItems;

	function __construct($activeClass, $defaultClass)
	{
		$this->ActiveClass = $activeClass;
		$this->DefaultClass = $defaultClass;
		$this->MenuItems = array();
	}

	function AddLink($pageId, $altPageIds)
	{
		$this->MenuItems[] = new oMenuItem($pageId, $altPageIds);
	}
}

class oMenuItem
{
	public $PageId;
	public $AltPageIds;

	//pageId: string for the main page id for the link
	//altPageId: comma separated string for all alt pages
	function __construct($pageId, $altPageIds)
	{
		$this->PageId = $pageId;
		$this->AltPageIds = (strlen($altPageIds) > 0) ? explode(",", $altPageIds) : array();
	}
}

class oPage
{
	public $Name;
	public $Title;
	public $Link;
	public $Template;
	public $Scripts;

	public $Site;
	public $Content;
	public $SiteSections;
	public $ContentSections;
	public $SiteVars;
	public $ContentVars;

	public $GetVars;
	public $PostVars;
	public $DB;

	//name: the name of the page
	//title: the title that will be displayed in the browser page title
	//link: the link to the page
	//template: the template for the page
	//scripts: the urls for the javascript files in a comma separated list
	function __construct($name, $title, $link, $template, $scripts)
	{
		$this->Name = $name;
		$this->Title = $title;
		$this->Link = $link;
		$this->Template = $template;
		$this->Scripts = (strlen($scripts) > 0) ? explode(",", $scripts) : array();

		$this->Site = "";
		$this->Content = "";
		$this->SiteSections = array();
		$this->ContentSections = array();
		$this->SiteSectionRows = array();
		$this->ContentSectionRows = array();
		$this->SiteSectionReqs = array();
		$this->ContentSectionReqs = array();
		$this->SiteVars = array();
		$this->PageVars = array();

		$this->GetVars = array();
		$this->PostVars = array();
	}

	function Prepare($db)
	{
		$this->DB = $db;

		//For clean get variables
		foreach ($_GET as $key => $value) {
			if (gettype($value) === "string") {
				$this->GetVars[$key] = ($db != null) ? $db->real_escape_string(htmlspecialchars($value)) : htmlspecialchars($value);
			} else {
				$this->GetVars[$key] = $value;
			}
		}

		//For clean post variables
		foreach ($_POST as $key => $value) {
			if (gettype($value) === "string") {
				$this->PostVars[$key] = ($db != null) ? $db->real_escape_string(htmlspecialchars($value)) : htmlspecialchars($value);
			} else {
				$this->PostVars[$key] = $value;
			}
		}
	}

	function Get($name, $default = "")
	{
		return (isset($this->GetVars[$name]) ? $this->GetVars[$name] : $default);
	}

	function Post($name, $default = "")
	{
		return (isset($this->PostVars[$name]) ? $this->PostVars[$name] : $default);
	}

	function Show()
	{
		//Content requests
		foreach ($this->ContentSectionReqs as $sectionId => $request) {
			$section = "";
			$tpl = $this->GetSection($sectionId);
			if ($request != null && $request->num_rows > 0) {
				while ($row = $request->fetch_assoc()) {
					$section .= ArrayReplace($row, $tpl);
				}
			}
			$this->SetSection($sectionId, $section);
		}

		//Content rows
		foreach ($this->ContentSectionRows as $sectionId => $rows) {
			$section = "";
			$tpl = $this->GetSection($sectionId);
			foreach ($rows as $key => $row) {
				$section .= ArrayReplace($row, $tpl);
			}
			$this->SetSection($sectionId, $section);
		}
		foreach ($this->SiteSectionRows as $sectionId => $rows) {
			$section = "";
			$tpl = $this->GetSiteSection($sectionId);
			foreach ($rows as $key => $row) {
				$section .= ArrayReplace($row, $tpl);
			}
			$this->SetSiteSection($sectionId, $section);
		}

		//Content sections
		foreach ($this->ContentSections as $sectionId => $section) {
			$this->Content = ReplaceContentSection($sectionId, $section, $this->Content);
		}
		foreach ($this->SiteSections as $sectionId => $section) {
			$this->Site = ReplaceContentSection($sectionId, $section, $this->Site);
		}

		//Vars
		$this->Content = ArrayReplace($this->PageVars, $this->Content);
		$this->Site = ArrayReplace($this->SiteVars, $this->Site);

		//ContentSection
		$this->Site = ValueReplace("PageContent", $this->Content, $this->Site);

		echo $this->Site;
	}

	public function HandleSiteIncludes(callable $getFileContentsFunc) {
		preg_match_all("/<!-- INCLUDE:(.+) -->/i", $this->Site, $matches);
		if (isset($matches) === true && count($matches) > 1) {
			foreach ($matches[1] as $match => $value) {
				$escapedVal = preg_quote($value, '/');
				$this->Site = ValueReplace("INCLUDE:$escapedVal", $getFileContentsFunc("./App/Views/$value"), $this->Site);
			}
		}
	}

	public function HandlePageIncludes(callable $getFileContentsFunc) {
		preg_match_all("/<!-- INCLUDE:(.+) -->/i", $this->Site, $matches);
		if (isset($matches) === true && count($matches) > 1) {
			foreach ($matches[1] as $match => $value) {
				$escapedVal = preg_quote($value, '/');
				$this->Site = ValueReplace("INCLUDE:$escapedVal", $getFileContentsFunc("./App/Views/$value"), $this->Content);
			}
		}
	}

	function GetSection($sectionId)
	{
		return GetContentSection($sectionId, $this->Content);
	}

	function GetSiteSection($sectionId)
	{
		return GetContentSection($sectionId, $this->Site);
	}

	function AddSectionRow($sectionId, $row)
	{
		$this->ContentSectionRows[$sectionId][] = $row;
	}

	function AddSiteSectionRow($sectionId, $row)
	{
		$this->SiteSectionRows[$sectionId][] = $row;
	}

	function SetSectionRequest($sectionId, $request)
	{
		$this->ContentSectionReqs[$sectionId] = $request;
	}

	function SetSiteSectionRequest($sectionId, $request)
	{
		$this->SiteSectionReqs[$sectionId] = $request;
	}

	function SetSection($sectionId, $section)
	{
		$this->ContentSections[$sectionId] = $section;
	}

	function SetSiteSection($sectionId, $section)
	{
		$this->SiteSections[$sectionId] = $section;
	}
}

function ValueReplace($find, $replace, $template)
{
	return preg_replace("/<!-- " . $find . " -->/i", $replace, $template);
}

function ArrayReplace($array, $template)
{
	foreach ($array As $find => $replace) {
		$template = ValueReplace($find, $replace, $template);
	}

	return $template;
}

function LoadPageTemplateRows($Result, $RowsName, $Template, $DateFormat = "")
{
	$Rows = "";
	$TmpRow = GetContentSection($RowsName, $Template);

	while ($Data = $Result->fetch_assoc()) {
		$Row = $TmpRow;

		foreach ($Data as $Find => $Replace) {
			if ((strpos($Find, "Date") > 0) && (strlen($DateFormat) > 0)) {
				$Replace = ReformatDate($Replace, $DateFormat);
			}
			$Row = str_replace("<!-- ".$Find." -->", $Replace, $Row);
		}

		$Rows .= $Row;
	}

	$Template = ReplaceContentSection($RowsName, $Rows, $Template);
	return $Template;
}

function GetContentSection($Name, $Source)
{
	$FindStart = "<!-- [" . $Name . "] -->";
	$FindEnd = "<!-- [/" . $Name . "] -->";

	$Start = strpos(" " . $Source, $FindStart) + strlen($FindStart) -1;
	$End = strpos(" " . $Source, $FindEnd) -1;

	if (($Start == -1) || ($End == -1)) {
		return "";
	}

	$Section = substr($Source, $Start, ($End - $Start));
	return $Section;
}

function ReplaceContentSection($Name, $Replace, $Source)
{
	$FindStart = "<!-- [" . $Name . "] -->";
	$FindEnd = "<!-- [/" . $Name . "] -->";

	$Start = strpos(" " . $Source, $FindStart) - 1;
	$End = strpos(" " . $Source, $FindEnd) - 1;
	$Length = $End - $Start + strlen($FindEnd);

	if (($Start == -1) || ($End == -1)) {
		return $Source;
	}

	$Source = substr_replace($Source, $Replace, $Start, $Length);
	return $Source;
}

?>