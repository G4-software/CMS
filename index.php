<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config/general.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/templater.php";

    $general_template = "test/template.html";
    $page = array(  'title' => "Test page",
                    'site_name' => "CMS",
                    'global_variable' => "This is global variable",
                    'object' => array(  'member_variable' => "This is member variable",
                                        'member_array' => array("This", "is", "a", "member", "array")),
                    'operand1' => 1,
                    'operand2' => array('member' => 1),
                    'array' => array("This", "is", "an", "array"));

    render($general_template, $page);
