<?php

namespace devpirates\MVC\Base;

abstract class Helper
{
    /**
     * @var \PDO
     */
    protected $db;

    public function __construct()
    {
        global $app;
        $this->db = $app->DB;
    }
}
