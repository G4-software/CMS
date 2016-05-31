<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config/general.php";

    function render($_template, $page, $template_type="file", $indent = 0)
    {
        $template = NULL;
        switch ($template_type)
        {
            case "file":
                $template = file(TEMPLATE_DIR.$_template);
                break;
            case "array":
                $template = $_template;
                break;
        }

        if($template_type == "file")
        {
            if(isset($_COOKIE['language']))
            {
                $language = $_COOKIE['language'];
            }
            else
            {
                $_language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
                if(in_array($_language, SITE_LANGUAGES))
                    $language = $_language;
                else
                    $language = "ru";
            }

            $generic_path_regex = '/(.+?)(\.[^.]*$|$)/';
            preg_match($generic_path_regex, $_template, $template_filename);
            $language_defines_path = "{$template_filename[1]}_$language.php";

            if(file_exists(TEMPLATE_DIR.$language_defines_path))
                require TEMPLATE_DIR.$language_defines_path;
        }

        $indent_line = '';
        for($a=1; $a<=$indent; $a++)
        {
            $indent_line = $indent_line.' ';
        }

        for($line_num=0; $line_num<count($template); $line_num++)
        {
            $template_line = $template[$line_num];

            $comment_regex = '/\{\/\/\}/';
            if(preg_match($comment_regex, $template_line))
            {
                $template_line = '';
            }

            $site_global_regex = '/\{\s*\*\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\}/';
            if(preg_match($site_global_regex, $template_line))
            {
                $template_line = preg_replace_callback( $site_global_regex,
                                                        function ($match)
                                                        {
                                                            return GLOBAL_VARS[$match[1]];
                                                        },
                                                        $template_line);
            }

            $language_defined_regex = '/\{\s*\@\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\}/';
            if(preg_match($language_defined_regex, $template_line))
            {
                $template_line = preg_replace_callback( $language_defined_regex,
                                                        function ($match) use ($language_defines)
                                                        {
                                                            return $language_defines[$match[1]];
                                                        },
                                                        $template_line);
            }

            $global_variable_include_regex = '/\{\s*\$\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\}/';
            if(preg_match($global_variable_include_regex, $template_line))
            {
                $template_line = preg_replace_callback( $global_variable_include_regex,
                                                        function ($match) use ($page)
                                                        {
                                                            return $page[$match[1]];
                                                        },
                                                        $template_line);
            }

            $member_variable_include_regex = '/\{\s*\$\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\.([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\}/';
            if(preg_match($member_variable_include_regex, $template_line))
            {
                $template_line = preg_replace_callback( $member_variable_include_regex,
                                                        function ($match) use ($page)
                                                        {
                                                            return $page[$match[1]][$match[2]];
                                                        },
                                                        $template_line);
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

                render($include_path[1], $page, "file", $spaces);

                $template_line = '';
            }

            $generic_condition_regex = '/\{\s*if\s+(.*)\s*\}/';
            if(preg_match($generic_condition_regex, $template_line, $condition))
            {
                $template_line = '';
                $condition_end_regex = '/\{\s*\~\s*if\s*\}/';
                $condition_content = NULL;

                $condition_counter = 0;
                do
                {
                    $line_num++;
                    $template_line = $template[$line_num];
                    $condition_content[] = $template_line;
                    if(preg_match($generic_condition_regex, $template_line))
                    {
                        $condition_counter += 1;
                    }
                    if(preg_match($condition_end_regex, $template_line))
                    {
                        $condition_counter -= 1;
                    }
                } while ($condition_counter>=0 || !preg_match($condition_end_regex, $template_line));
                $template_line = '';
                array_pop($condition_content);

                $comparsion_regex = '/([^\s]+)\s*(==|!=|<=|>=|<|>)\s*([^\s]+)/';
                if(preg_match($comparsion_regex, $condition[1], $comparsion))
                {
                    $first_operand = NULL;
                    $second_operand = NULL;
                    $member_variable_regex = '/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\.([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
                    $number_regex = '/[0-9]*/';
                    if(preg_match($member_variable_regex, $comparsion[1], $operand_info))
                    {
                        $first_operand = $page[$operand_info[1]][$operand_info[2]];
                    }
                    // elseif(preg_match($number_regex, $comparsion[1]))
                    // {
                    //     $first_operand = $comparsion[1];
                    // }
                    else
                    {
                        $first_operand = $page[$comparsion[1]];
                    }
                    if(preg_match($member_variable_regex, $comparsion[3], $operand_info))
                    {
                        $second_operand = $page[$operand_info[1]][$operand_info[2]];
                    }
                    elseif(preg_match($number_regex, $comparsion[3]))
                    {
                        $second_operand = $comparsion[3];
                    }
                    else
                    {
                        $second_operand = $page[$comparsion[3]];
                    }

                    switch ($comparsion[2])
                    {
                        case '==':
                            if($first_operand == $second_operand)
                            {
                                render($condition_content, $page, "array", $indent);
                            }
                            break;

                        case '!=':
                            if($first_operand != $second_operand)
                            {
                                render($condition_content, $page, "array", $indent);
                            }
                            break;

                        case '<=':
                            if($first_operand <= $second_operand)
                            {
                                render($condition_content, $page, "array", $indent);
                            }
                            break;

                        case '>=':
                            if($first_operand >= $second_operand)
                            {
                                render($condition_content, $page, "array", $indent);
                            }
                            break;

                        case '<':
                            if($first_operand < $second_operand)
                            {
                                render($condition_content, $page, "array", $indent);
                            }
                            break;

                        case '>':
                            if($first_operand > $second_operand)
                            {
                                render($condition_content, $page, "array", $indent);
                            }
                            break;
                    }
                }
            }

            $generic_for_regex = "/\{\s*for\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s+in\s+([^\s]+)\s*\}/";
            if(preg_match($generic_for_regex, $template_line, $for_data))
            {
                $template_line = '';                      //{for a in b}
                $for_end_regex = '/\{\s*\~\s*for\s*\}/';  //    [1]  [2]
                $for_content = NULL;                      //    var array

                $for_counter = 0;
                do
                {
                    $line_num++;
                    $template_line = $template[$line_num];
                    $for_content[] = $template_line;
                    if(preg_match($generic_for_regex, $template_line))
                    {
                        $for_counter += 1;
                    }
                    if(preg_match($for_end_regex, $template_line))
                    {
                        $for_counter -= 1;
                    }
                } while ($for_counter>=0 || !preg_match($for_end_regex, $template_line));
                $template_line = '';
                array_pop($for_content);

                $member_variable_regex = '/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\.([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
                $array = NULL;
                if(preg_match($member_variable_regex, $for_data[2], $operand_info))
                {
                    $array = $page[$operand_info[1]][$operand_info[2]];
                }
                else
                {
                    $array = $page[$for_data[2]];
                }

                foreach ($array as $current_for_element)
                {
                    $page_t = $page;
                    $page_t[$for_data[1]] = $current_for_element;

                    render($for_content, $page_t, "array", $indent);
                }
            }

            if(!empty($template_line))
            {
                echo $indent_line.$template_line;
            }
        }
    }
