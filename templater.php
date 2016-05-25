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
        $indent_line = '';
        for($a=1; $a<=$indent; $a++)
        {
            $indent_line = $indent_line.' ';
        }

        for($line_num=0; $line_num<count($template); $line_num++)
        {
            $template_line = $template[$line_num];

            $comment_regex = '/\{\#\}/';
            if(preg_match($comment_regex, $template_line))
            {
                $template_line = '';
            }

            $global_variable_include_regex = '/\{\s*\$\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\}/';
            if(preg_match($global_variable_include_regex, $template_line))
            {
                $template_line = preg_replace_callback( $global_variable_include_regex,
                                                        function ($match) use ($page)
                                                        {
                                                            return $page[$match[1]];
                                                        },
                                                        $template[$line_num]);
            }

            $member_variable_include_regex = '/\{\s*\$\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\.([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\}/';
            if(preg_match($member_variable_include_regex, $template_line))
            {
                $template_line = preg_replace_callback( $member_variable_include_regex,
                                                        function ($match) use ($page)
                                                        {
                                                            return $page[$match[1]][$match[2]];
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

                render($include_path[1], $page, "file", $spaces);

                $template_line = '';
            }

            $generic_condition_regex = '/\{\s*if\s+(.*)\s*\}/';
            if(preg_match($generic_condition_regex, $template_line, $condition))
            {
                $template_line = '';
                $condition_end_regex = '/\{\s*\~\s*if\s*\}/';
                $condition_content = NULL;
                do
                {
                    $line_num++;
                    $template_line = $template[$line_num];
                    $condition_content[] = $template_line;
                } while (!preg_match($condition_end_regex, $template_line));
                $template_line = '';
                array_pop($condition_content);

                $comparsion_regex = '/([^ ]+)\s*(==|!=|<=|>=|<|>)\s*([^ ]+)/';
                if(preg_match($comparsion_regex, $condition[1], $comparsion))
                {
                    $first_operand = NULL;
                    $second_operand = NULL;
                    $member_variable_regex = '/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\.([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
                    if(preg_match($member_variable_regex, $comparsion[1], $operand_info))
                    {
                        $first_operand = $page[$operand_info[1]][$operand_info[2]];
                    }
                    else
                    {
                        $first_operand = $page[$comparsion[1]];
                    }
                    if(preg_match($member_variable_regex, $comparsion[3], $operand_info))
                    {
                        $second_operand = $page[$operand_info[1]][$operand_info[2]];
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

            // $for_regex = '/\{\s*for\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s+in\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\}/';
            // if(preg_match($for_regex, $template_line, $for_data))
            // {
            //     $template_line = '';                      //{for a in b}
            //     $for_end_regex = '/\{\s*\~\s*for\s*\}/';  //    [1]  [2]
            //     $for_content = NULL;                      //    var array
            //     do
            //     {
            //         $line_num++;
            //         $template_line = $template[$line_num];
            //         $for_content[] = $template_line;
            //     } while (!preg_match($for_end_regex, $template_line));
            //     $template_line = '';
            //     array_pop($for_content);
            //
            //     foreach ($page[$for_data[2]] as $current_for_element)
            //     {
            //         $page_t = $page;
            //         $page_t[$for_data[1]] = $current_for_element;
            //
            //         render($for_content, $page_t, "array", $indent);
            //     }
            // }

            $generic_for_regex = "/\{\s*for\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s+in\s+([^ ]+)\s*\}/";
            if(preg_match($generic_for_regex, $template_line, $for_data))
            {
                $template_line = '';                      //{for a in b}
                $for_end_regex = '/\{\s*\~\s*for\s*\}/';  //    [1]  [2]
                $for_content = NULL;                      //    var array
                do
                {
                    $line_num++;
                    $template_line = $template[$line_num];
                    $for_content[] = $template_line;
                } while (!preg_match($for_end_regex, $template_line));
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
