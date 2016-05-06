<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config/general.php";

    function render($template_path, $page, $indent = 0)
    {
        $template = file(TEMPLATE_DIR.$template_path);
        $indent_line = '';
        for($a=1; $a<=$indent; $a++)
        {
            $indent_line = $indent_line.' ';
        }

        foreach ($template as $line_num => $template_line)
        {
            $variable_regex = '/\{\s*\$\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\}/';
            if(preg_match($variable_regex, $template_line))
            {
                $template_line = preg_replace_callback( $variable_regex,
                function ($match) use ($page)
                {
                    return $page[$match[1]];
                },
                $template[$line_num]);
            }

            $include_regex = '/\{\s*\#\s*([^\0\'\"]*)\s*\}/';
            if(preg_match($include_regex, $template_line, $include_path))
            {
                $indent_regex = '/^(\s+)/';
                if(preg_match($indent_regex, $template_line, $match))
                {
                    $spaces = $indent+strlen($match[1]);
                }
                else
                {
                    $spaces = $indent;
                }

                render($include_path[1], $page, $spaces);

                $template_line = '';
            }

            // $condition_regex = '/\{\s*\?\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\}/';
            // if(preg_match($condition_regex, $template_line, $condition))
            // {
            //     print_r($condition);
            // }

            if(!empty($template_line))
            {
                echo $indent_line.$template_line;
            }
        }
    }
