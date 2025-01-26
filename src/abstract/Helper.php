<?php

namespace devpirates\MVC\Base;

use devpirates\MVC\TemplateMVCApp;

abstract class Helper
{
    /**
     * @var \PDO
     */
    protected $db;

    /*
    * @var \TemplateMVCApp
    */
    protected $app;

    public function __construct(TemplateMVCApp $app)
    {
        $this->app = $app;
        $this->db = $app->DB;
    }
}
