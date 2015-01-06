<?php
/**
 * Icon browser for Horde themes.
 *
 * This script requires the user to be authenticated (to prevent abuses).
 *
 * Copyright 2004-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2004-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL-2
 * @package   Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde');

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
            (in_array(substr($val->getFilename(), -4), array('.png', '.gif', 'jpg')))) {
            $imgs[] = strval($val);
        }
    }

    if (count($imgs)) {
        foreach ($imgs as $img) {
            echo Horde_Themes_Image::tag(
                Horde_Themes::img(
                    str_replace($img_fs . DIRECTORY_SEPARATOR, '', $img),
                    array(
                        'app' => $app,
                        'theme' => 'default'
                    )
                ),
                array(
                    'alt' => $img,
                    'attr' => array(
                        'hspace' => 10,
                        'title' => $img,
                        'vspace' => 10
                    )
                )
            );
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
