<?php namespace devpirates\MVC;
class Menu
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
		$this->MenuItems[] = new MenuItem($pageId, $altPageIds);
	}
}

class MenuItem
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
?>