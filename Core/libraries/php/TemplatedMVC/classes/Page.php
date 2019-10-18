<?php
class Page
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
		$this->ContentVars = array();
	}

	public function Show(bool $echoResult = true): ?string
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
		$this->Content = ArrayReplace($this->ContentVars, $this->Content);
		$this->Site = ArrayReplace($this->SiteVars, $this->Site);

		//ContentSection
		$this->Site = ValueReplace("PageContent", $this->Content, $this->Site);

		if ($echoResult === true) {
			echo $this->Site;
		} else {
			return $this->Site;
		}
	}

	public function HandleSiteIncludes(callable $getFileContentsFunc) {
		preg_match_all("/<!-- INCLUDE:(.+) -->/", $this->Site, $matches);
		if (isset($matches) === true && count($matches) > 1) {
			foreach ($matches[1] as $match => $value) {
				$escapedVal = preg_quote($value, '/');
				$this->Site = ValueReplace("INCLUDE:$escapedVal", $getFileContentsFunc(VIEWS_PATH . "/$value"), $this->Site);
			}
		}
	}

	public function HandlePageIncludes(callable $getFileContentsFunc) {
		preg_match_all("/<!-- INCLUDE:(.+) -->/", $this->Content, $matches);
		if (isset($matches) === true && count($matches) > 1) {
			foreach ($matches[1] as $match => $value) {
				$escapedVal = preg_quote($value, '/');
				$this->Content = ValueReplace("INCLUDE:$escapedVal", $getFileContentsFunc(VIEWS_PATH . "/$value"), $this->Content);
			}
		}
	}

    private function object_to_array($obj) {
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($_arr as $key => $val) {
            $val = (is_array($val) || is_object($val)) ? $this->object_to_array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
	}
	
	public function HandleModel($model, string $section = null) {
		if (isset($model)) {
			$modelProps = $this->object_to_array($model);
            if (count($modelProps) > 0) {
				if (isset($section) && $section !== null) {
					foreach ($modelProps as $key => $value) {
						if ($value != null) {
							$section = ValueReplace($key, $value, $section);
						}
					}
					return $section;
				} else {
					foreach ($modelProps as $key => $value) {
						$sectionId = "MODEL:$key";
						if ($value !== null) {
							if (is_array($value) === true && count($value) > 0) {
								$section = $this->GetSection($sectionId);
								$sectionData = "";
								foreach ($value as $vKey => $vValue) {
									if (is_object($vValue) === true || is_array($vValue) === true) {
										$sectionData .= $this->HandleModel($vValue, $section); // only works one layer deep
									} else {
										$sectionData .= ArrayReplace(array("Name" => $vValue), $section);
									}
								}
								$this->SetSection($sectionId, $sectionData);
							} else {
								$this->Content = ValueReplace($sectionId, $value, $this->Content);
							}
						}
					}
				}
            }
        } else {
			preg_match_all("/<!-- \[?MODEL:([^\]\-]+)\]? -->/", $this->Content, $matches);
			if (isset($matches) === true && count($matches) > 1) {
				foreach ($matches[1] as $match => $value) {
					$key = "MODEL:" . preg_quote($value, '/');
					if (strpos($matches[0][$match], '[') !== false) {
						$this->SetSection($key, "");
					} else {
						$this->Content = ValueReplace($key, "", $this->Content);
					}
				}
			}
		}
	}

	public function GetSection(string $sectionId)
	{
		return GetContentSection($sectionId, $this->Content);
	}

	public function GetSiteSection($sectionId)
	{
		return GetContentSection($sectionId, $this->Site);
	}

	public function AddSectionRow($sectionId, $row)
	{
		$this->ContentSectionRows[$sectionId][] = $row;
	}

	public function AddSiteSectionRow($sectionId, $row)
	{
		$this->SiteSectionRows[$sectionId][] = $row;
	}

	public function SetSectionRequest($sectionId, $request)
	{
		$this->ContentSectionReqs[$sectionId] = $request;
	}

	public function SetSiteSectionRequest($sectionId, $request)
	{
		$this->SiteSectionReqs[$sectionId] = $request;
	}

	public function SetSection($sectionId, $section)
	{
		$this->ContentSections[$sectionId] = $section;
	}

	public function SetSiteSection($sectionId, $section)
	{
		$this->SiteSections[$sectionId] = $section;
	}
}

function ValueReplace(string $find, string $replace, string $template)
{
	return preg_replace("/<!-- " . $find . " -->/i", $replace, $template);
}

function ArrayReplace(array $array, string $template)
{
	foreach ($array As $find => $replace) {
		$template = ValueReplace($find, $replace, $template);
	}

	return $template;
}

function LoadPageTemplateRows(object $Result, string $RowsName, string $Template, string $DateFormat = "")
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

function GetContentSection(string $Name, string $Source)
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

function ReplaceContentSection(string $Name, string $Replace, string $Source)
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