<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config/general.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/templater.php";

    if(empty($_POST) && !empty($_GET))
    {
        $template = NULL;
        if(file_exists(TEMPLATE_DIR.$_GET['template']))
        {
            $template = file(TEMPLATE_DIR.$_GET['template']);
        }

        $translate_vars = NULL;
        foreach ($template as $template_line)
        {
            $language_defined_regex = '/\{\s*\@\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\}/';
            if(preg_match($language_defined_regex, $template_line, $variables))
            {
                for($a=1; $a<count($variables); $a++)
                {
                    $translate_vars[] = $variables[$a];
                }
            }
        }

        $page = array(  'template_filename' => $_GET['template']);
        foreach (SITE_LANGUAGES as $language)
        {
            $page['languages'][] = $language;
        }
        foreach ($translate_vars as $variable)
        {
            $page['variables'][] = $variable;
        }

        render("translations.html", $page);
    }
    elseif(!empty($_POST) && empty($_GET))
    {
        $translations = NULL;
        foreach ($_POST as $translation_key => $translation)
        {
            $translation_key_regex = '/^([a-z]{2})_([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/';
            if(preg_match($translation_key_regex, $translation_key, $key_data))
            {
                if(in_array($key_data[1], SITE_LANGUAGES))
                {
                    $translations[$key_data[1]][$key_data[2]] = $translation;
                }
            }
        }

        $template_path = $_POST['template_filename'];
        $generic_path_regex = '/(.+?)(\.[^.]*$|$)/';
        preg_match($generic_path_regex, $template_path, $template_filename);
        foreach ($translations as $language => $language_translations)
        {
            // $language_defines[] = "<?php";
            $language_defines[] = "\$language_defines = array (";
            foreach ($language_translations as $label => $translation)
            {
                $language_defines[] = "'$label' => \"$translation\"";
            }
            $language_defines[] = ");";

            echo "$language:<br />";
            foreach ($language_defines as $line)
            {
                echo "$line<br />";
            }

            $language_defines = NULL;
        }
    }
