<?php
/**
 * Icon browser for Horde themes.
 *
 * Copyright 2004-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$apps = $registry->listAllApps();
sort($apps);

$url = new Horde_Url('icon_browser.php');
$vars = $injector->getInstance('Horde_Variables');

if (($app = basename($vars->app)) && in_array($app, $apps)) {
    $img = Horde_Themes::img(null, array(
        'app' => $app,
        'theme' => 'default'
    ));
    $img_fs = $img->fs;

    // Throws Exception on error.
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($img_fs));

    // Provide a non-white background for eyeballing transparency.
    echo '<html><body bgcolor="#aaaaaa">' .
         '<h2>' . sprintf(_("Icons for %s"), $registry->get('name', $app)) . '</h2>';

    foreach ($iterator as $val) {
        if ($val->isFile() &&
            (substr($val->getFilename(), -4) == '.png')) {
            $imgs[] = strval($val);
        }
    }

    if (count($imgs)) {
        foreach ($imgs as $png) {
            echo Horde::img(Horde_Themes::img(str_replace($img_fs . DIRECTORY_SEPARATOR, '', $png), $app), $png, array(
                'hspace' => 10,
                'title' => htmlspecialchars($png),
                'vspace' => 10
            ));
        }
    } else {
        echo _("No icons found.");
    }

    echo '<p /><a href="' . $url . '">Return to app browser</a></body></html>';
} else {
    // List apps.
    foreach ($apps as $app) {
        if ($name = $registry->get('name', $app)) {
            echo Horde::link($url->add('app', $app)) . htmlspecialchars($name) . ' [' . htmlspecialchars($app) . ']</a><br />';
        }
    }
}
