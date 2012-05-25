#!/usr/bin/env php
<?php
/**
 * Horde Module-from-Skeleton helper. This script allows to convert
 * the example skeleton project into a real module stup that can
 * develop into your own module.
 *
 * Usage: horde-generate-module.php MODULENAME "AUTHOR"
 *
 * Options:
 *
 *  - MODULENAME: The name of the new module that you wish to create.
 *  - AUTHOR: Your name and mail address (e.g. "John Doe <john@doe.org>")
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Ralf Lang <lang@b1-systems.de>
 * @author Gunnar Wrobel <wrobel@horde.org>
 */

function help()
{
    echo 'horde-generate-module.php MODULENAME' . "\n\n";
    echo 'Options:' . "\n\n";
    echo ' - MODULENAME: The name of the new module that you wish to create.' . "\n";
    echo ' - AUTHOR: Your name and mail address (e.g. "John Doe <john@doe.org>")' . "\n\n";
}

function recursiveCopy($path, $dest)
{
    @mkdir($dest);
    $objects = scandir($path);
    if (sizeof($objects) > 0) {
        foreach ($objects as $file) {
            if ($file == "." || $file == "..") {
                continue;
            }
            if (is_dir($path . '/' . $file)) {
                recursiveCopy($path . '/' . $file, $dest .  '/' . $file);
            } else {
                copy($path . '/' . $file, $dest . '/' .$file);
            }
        }
    }
}

function analysedir($path, $list)
{
    $handle = opendir($path);
    while (false !== ($file = readdir($handle))) {
        if ($file!='.' && $file!='..') {
            $file = $path . '/' . $file;
            if (!is_dir($file)) {
                $list[count($list)]=$file;
            } else {
                $list += analysedir($file, $list);
            }
        }
    }
    return $list;
}
 
function substitute_skeleton($filename, $modulname, $author)
{
    $prjUC=strtoupper(trim($modulname));
    $prjLC=strtolower($prjUC);
    $prjMC=substr($prjUC, 0, 1) . substr($prjLC, 1, strlen($prjLC)-1);
 
    $filehandle=fopen(trim($filename), 'r');
    $file=fread($filehandle, filesize($filename));
    fclose($filehandle);
    $newfile=str_replace(
        array('SKELETON', 'Skeleton', 'skeleton', 'Your Name <you@example.com>'),
        array($prjUC, $prjMC, $prjLC, $author),
        $file
    );
    $filehandle=fopen(trim($filename), 'w');
    fwrite($filehandle, $newfile);
    fclose($filehandle);
}
 
 
//
// ------------------- Main-Code --------------------
//
 
if (count($_SERVER['argv']) == 3) {
    // Preparation
    $module = trim($_SERVER['argv'][1]);
    $author = trim($_SERVER['argv'][2]);

    $skeleton_path = __DIR__ . '/../../skeleton';
    if (!is_dir($skeleton_path)) {
        echo 'Assumed origin of the skeleton module (' . $skeleton_path . ')does not seem to exist!';
        exit(1);
    }
    $module_path = dirname($skeleton_path) . '/' . $module;
    recursiveCopy($skeleton_path, $module_path);

    // Fetch filelist
    $list = array();
    $list = analysedir($module_path, $list);
 
    // Modify each file
    foreach ($list as $file) {
        substitute_skeleton($file, $module, $author);
    }

    rename(
        $module_path . '/test/Skeleton',
        $module_path . '/test/' . ucfirst($module)
    );

    rename(
        $module_path . '/locale/skeleton.pot',
        $module_path . '/locale/' . $module . '.pot'
    );

    rename(
        $module_path . '/migration/1_skeleton_base_tables.php',
        $module_path . '/migration/1_' . $module . '_base_tables.php'
    );
    echo 'Started new Module in ' . realpath($module_path) . "!\n\n";
    echo 'Register the new Module with a file in the config/registry.d directory:' . "\n\n";
    echo '<?php' . "\n";
    echo '$this->applications[\'' . $module . '\'] = array(\'name\' => _("' . ucfirst($module) . '"));'. "\n\n";
} else {
  help();
  exit(1);
}