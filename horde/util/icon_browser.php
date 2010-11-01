<?php
/**
 * Icon browser for Horde themes.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$apps = $registry->listApps(array('notoolbar', 'active', 'admin', 'inactive', 'hidden'), true);
ksort($apps);

$url = new Horde_Url('icon_browser.php');
$vars = Horde_Variables::getDefaultVariables();

if (($app = basename($vars->app)) && isset($apps[$app])) {
    // Provide a non-white background for eyeballing transparency.
    echo '<html><body bgcolor="#aaaaaa">';

    $themeDir = $registry->get('themesfs', $app);
    if (!is_dir($themeDir)) {
        exit(sprintf(_("Themes directory \"%s\" not found."), $themeDir));
    }

    // Base graphics for now, feel free to add theme support.
    $basedir = $dir = $themeDir . DIRECTORY_SEPARATOR . 'graphics';
    if (!is_dir($dir)) {
        exit(sprintf(_("Base graphics directory \"%s\" not found."), $dir));
    }

    if (($subdir = basename(filter_var($vars->subdir, FILTER_SANITIZE_STRING)))) {
        $dir .= DIRECTORY_SEPARATOR . $subdir;
        if (!is_dir($dir)) {
            exit(sprintf(_("Subdirectory \"%s\" not found."), $dir));
        }
    }

    // Breadcrumbs.
    echo Horde::link($url) . _("Application List") . '</a><br />';
    if (!empty($subdir)) {
        echo Horde::link($url->copy()->add('app', $app)) . $registry->get('name', $app) . '</a><br />';
    }

    echo '<br />';

    // Show icons for app.
    echo '<h2>' . sprintf(_("Icons for %s"), $registry->get('name', $app)) . (!empty($subdir) ? '/' . $subdir : '') . '</h2>';

    // List subdirectories.
    $dirs = $imgs = array();
    try {
        $di = new DirectoryIterator($dir);
        foreach ($di as $val) {
            if ($val->isDir() && !$val->isDot()) {
                $dirs[] = Horde::link($url->copy()->add(array(
                    'subdir' => basename($val->getFilename()),
                    'app' => $app
                ))) . basename($val->getFilename()) . '</a>';
            } elseif ($val->isFile() &&
                      (substr($val->getFilename(), -4) == '.png')) {
                $imgs[] = $val->getPathname();
            }
        }
    } catch (UnexpectedValueException $e) {}

    foreach ($dirs as $val) {
        echo $val . '<br />';
    }

    echo '<br />';

    if (count($imgs)) {
        foreach ($imgs as $png) {
            echo Horde::img(Horde_Themes::img(str_replace($basedir . DIRECTORY_SEPARATOR, '', $png), $app), $png, array('hspace' => 10, 'vspace' => 10));
        }
    } else {
        echo _("No icons found.");
    }

    echo '</body></html>';
} else {
    // List apps.
    foreach ($apps as $app) {
        echo Horde::link($url->add('app', $app)) . $registry->get('name', $app) . '</a><br />';
    }
}
