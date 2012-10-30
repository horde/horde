<?php
/**
 * PHP Shell.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:phpshell')
));

$vars = $injector->getInstance('Horde_Variables');

$apps_tmp = $registry->listApps();
$apps = array();
foreach ($apps_tmp as $app) {
    // Make sure the app is installed.
    if (!file_exists($registry->get('fileroot', $app))) {
        continue;
    }

    $apps[$app] = $registry->get('name', $app) . ' (' . $app . ')';
}
asort($apps);

$application = $vars->get('app', 'horde');
$command = trim($vars->php);

$title = _("PHP Shell");

$view = new Horde_View(array(
    'templatePath' => HORDE_TEMPLATES . '/admin'
));
$view->addHelper('Horde_Core_View_Helper_Help');
$view->addHelper('Text');

$view->action = Horde::url('admin/phpshell.php');
$view->application = $application;
$view->apps = $apps;
$view->command = $command;
$view->title = $title;

if ($command) {
    $pushed = $registry->pushApp($application);

    $part = new Horde_Mime_Part();
    $part->setContents($command);
    $part->setType('application/x-httpd-phps');
    $part->buildMimeIds();

    $pretty = $injector->getInstance('Horde_Core_Factory_MimeViewer')->create($part)->render('inline');
    $view->pretty = $pretty[1]['data'];
    Horde::startBuffer();
    try {
        eval($command);
    } catch (Exception $e) {
        echo $e;
    }
    $view->command_exec = Horde::endBuffer();

    if ($pushed) {
        $registry->popApp();
    }
}

$page_output->addScriptFile('stripe.js', 'horde');
$page_output->header(array(
    'title' => $title
));
require HORDE_TEMPLATES . '/admin/menu.inc';
echo $view->render('phpshell');
$page_output->footer();
