#!@php_bin@
<?php
/**
 * This script does some checking to make sure images are synchronised
 * across themes.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  devtools
 * @author   Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none', 'cli' => true));

/* Get any options. */
$simple = false;
$horde_base = null;
$ignore = array();
if (isset($argv)) {
    /* Get rid of the first arg which is the script name. */
    array_shift($argv);
    while ($arg = array_shift($argv)) {
        if ($arg == '--help') {
            print_usage();
        } elseif ($arg == '-s') {
            $simple = true;
        } elseif (strpos($arg, '-i') === 0) {
            list(,$ignore[]) = explode('=', $arg);
        } elseif (file_exists($arg . '/config/registry.php')) {
            $horde_base = $arg;
        } else {
            print_usage("Unrecognised option $arg");
        }
    }
}

if ($horde_base === null) {
    print_usage("You must specify the base path to Horde.");
}

/* Get the apps and start doing checks. */
$apps = $registry->listApps(array('hidden', 'notoolbar', 'active', 'admin'));

/* Get a list of themes. */
$themes = array();
$themes_dir = $registry->get('themesfs', 'horde');
if ($handle = opendir($themes_dir)) {
    while ($file = readdir($handle)) {
        if ($file == '.' || $file == '..' || $file == 'CVS' ||
            $file == '.svn' ||
            !file_exists("$themes_dir/$file/themed_graphics") ||
            !file_exists("$themes_dir/$file/graphics")) {
            continue;
        }

        /* Store the apps and their theme directories. */
        foreach ($apps as $app) {
            $dir = $registry->get('themesfs', $app) . '/' . $file . '/graphics';
            if (is_dir($dir)) {
                $themes[$app][$file] = $dir;
            }
        }
    }
}

foreach ($apps as $app) {
    /* Skip applications without icon themes. */
    if (!isset($themes[$app])) {
        continue;
    }

    /* Set up some dirs. */
    $themes_dir = $registry->get('themesfs', $app);
    $horde_icon_dir = $themes_dir . '/graphics';

    /* Sanity check for the directories. */
    if (!file_exists($horde_icon_dir)) {
        continue;
    }

    /* Get a list of all horde images recursively. */
    $horde_icon_list = array();
    readDirRecursively($horde_icon_dir, $horde_icon_dir, $horde_icon_list);

    /* Loop through themes that replace icons and check for differences. */
    foreach ($themes[$app] as $theme => $theme_icon_dir) {
        $theme_icon_list = array();
        readDirRecursively($theme_icon_dir, $theme_icon_dir, $theme_icon_list);

        /* Check for icons that are in the Horde base theme and not in the
         * custom theme. */
        $diff = array_diff($horde_icon_list, $theme_icon_list);
        /* Don't bother reporting anything for themes that have all the horde
         * base icons. */
        if (empty($diff)) {
            continue;
        }

        $cli->writeln($cli->red(sprintf('[%s] "%s" theme missing these icons:',
                                strtoupper($app),
                                $theme)));
        sort($diff);
        foreach ($diff as $icon) {
            $cli->writeln($icon);
        }

        /* Check if only doing a Horde base theme to custom theme check. Skip
         * the reverse checking if true. */
        if ($simple) {
            continue;
        }

        /* Check for icons that are in the Horde base theme and not in the
         * custom theme. */
        $diff = array_diff($theme_icon_list, $horde_icon_list);
        /* Don't bother reporting anything for themes that don't have any icons
         * more than the base theme. */
        if (empty($diff)) {
            continue;
        }

        $cli->writeln($cli->blue(sprintf('[%s] "%s" theme has these extra icons:',
                                strtoupper($app),
                                $theme)));
        sort($diff);
        foreach ($diff as $icon) {
            $cli->writeln($icon);
        }
    }
}

$cli->writeln($cli->green('Done.'));
exit;

/**
 * Loops through the directory recursively and stores the found
 * graphics into an array.
 */
function readDirRecursively($path, $basepath, &$list)
{
    global $ignore;

    if ($handle = opendir($path)) {
        while ($file = readdir($handle)) {
            if ($file == '.' || $file == '..' ||
                $file == 'CVS' || $file == '.svn') {
                continue;
            }
            if (is_dir("$path/$file")) {
                readDirRecursively("$path/$file", $basepath, $list);
            } else {
                foreach ($ignore as $pattern) {
                    if (preg_match($pattern, $file)) {
                        continue 2;
                    }
                }
                $list[] = substr($path, strlen($basepath)) . "/$file";
            }
        }
        closedir($handle);
    }

}

function print_usage($message = '')
{

    if (!empty($message)) {
        print "themes_check.php: $message\n\n";
    }

    print <<<USAGE
Usage: themes_check.php [OPTION] /path/to/horde

Possible options:
  -s            Do only a simple check for any Horde base theme graphics that
                are missing from the other themes, and no check of the
                opposite.
  -i=PATTERN    Insert any valid regex pattern to ignore files from being
                checked. You can enter multiple -i options to include multiple
                patterns. For example: -i="/xcf$/ to ignore any original
                GIMP files.

USAGE;
    exit;
}
