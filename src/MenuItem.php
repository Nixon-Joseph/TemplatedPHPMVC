<?php

namespace devpirates\MVC;

class MenuItem
{
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
    /**
     * String representation of icon
     * Could be class string, or attribute value - whatever you need.
     *
     * @var string
     */
    public $Icon;

    public function __construct(string $name, string $link, ?string $activeClass = null, ?string $icon = null, string $matchPattern = null)
    {
        $this->Name = $name;
        $this->Link = $link;
        $this->ActiveClass = isset($activeClass) && strlen($activeClass) ? $activeClass : '';
        $this->Icon = isset($icon) && strlen($icon) ? $icon : '';
        $this->MatchPattern = "/^" . (isset($matchPattern) && strlen($matchPattern) ? $matchPattern : preg_replace('/\//', '\/', $link)) . "$/i";
    }
}
