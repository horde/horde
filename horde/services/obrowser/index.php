<?php
/**
 * Copyright 2004-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

$vars = $injector->getInstance('Horde_Variables');

$path = $vars->path;
if (empty($path)) {
    $list = array();
    $apps = $registry->listApps(null, false, Horde_Perms::READ);
    foreach ($apps as $app) {
        if ($registry->hasMethod('browse', $app)) {
            $list[$app] = array('name' => $registry->get('name', $app),
                                'icon' => $registry->get('icon', $app),
                                'browseable' => true);
        }
    }
} else {
    $pieces = explode('/', $path);
    $list = $registry->callByPackage($pieces[0], 'browse', array('path' => $path));
}

if (!count($list)) {
    $notification->push(_("Nothing to browse, go back."), 'horde.warning');
}

$rows = array();
foreach ($list as $path => $values) {
    $row = array();

    // Set the icon.
    if (!empty($values['icon'])) {
        $row['icon'] = Horde::img($values['icon'], $values['name'], '', '');
    } elseif (!empty($values['browseable'])) {
        $row['icon'] = Horde::img('tree/folder.png');
    } else {
        $row['icon'] = Horde::img('tree/leaf.png');
    }

    // Set the name/link.
    $name = $values['name'] ?: basename($path);
    if (!empty($values['browseable'])) {
        $url = Horde::url('services/obrowser', false, array('app' => 'horde'))->add('path', $path);
        $row['name'] = $url->link() . htmlspecialchars($name) . '</a>';
    } else {
        $js = "return chooseObject('" . addslashes($path) . "');";
        $row['name'] = Horde::link('#', sprintf(_("Choose %s"), $name), '', '', $js) . htmlspecialchars($name) . '</a>';
    }

    $rows[] = $row;
}

$view = new Horde_View(array(
    'templatePath' => HORDE_TEMPLATES . '/services'
));
$view->addHelper('Horde_Core_View_Helper_Image');

$view->rows = $rows;

$page_output->addScriptFile('obrowser.js', 'horde');
$page_output->addScriptFile('stripe.js', 'horde');
$page_output->topbar = $page_output->sidebar = false;

$page_output->header();
$notification->notify(array('listeners' => 'status'));
echo $view->render('obrowser');
$page_output->footer();
