<?php
    header("Cache-Control: no-cache, must-revalidate"); //Basic settings to not allow browser make cache
    header("Expires: Sat, 01 Jan 1980 05:00:00 GMT");
    error_reporting(E_ALL);

    require_once $_SERVER['DOCUMENT_ROOT']."/config/db_connect.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/config/global_vars.php";

    $site_languages = array("ru","en");
    define("SITE_LANGUAGES",$site_languages);
    define("GLOBAL_VARS",$global_vars);
    define("TEMPLATE_DIR",$_SERVER['DOCUMENT_ROOT']."/templates/");
