<?php
class MenuItem {
    /**
     * class used for current nav item
     *
     * @var string
     */
    public $ActiveClass;
    /**
     * Location for link
     *
     * @var string
     */
    public $Link;
    /**
     * Display value for menu link
     *
     * @var string
     */
    public $Name;
    /**
     * Regex pattern used to determine if the nav item is the active one
     *
     * @var string
     */
    public $MatchPattern;

    public function __construct(string $name, string $link, ?string $activeClass = null, string $matchPattern = null) {
        $this->Name = $name;
        $this->Link = $link;
        $this->ActiveClass = isset($activeClass) && strlen($activeClass) ? $activeClass : '';
        $this->MatchPattern = "/^" . (isset($matchPattern) && strlen($matchPattern) ? $matchPattern : preg_replace('/\//', '\/', $link)) . "$/i";
    }
}
?>