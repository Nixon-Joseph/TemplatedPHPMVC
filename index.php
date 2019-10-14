<?php
/**
 * @author nieminen <nieminen432@gmail.com>
 */
require __DIR__ . '/Core/TemplateMVCApp.php';
$app = new TemplateMVCApp();
$app->Autoload();
$app->Config();
$app->Start();
?>