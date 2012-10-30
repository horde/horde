<?php
/**
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
    'permission' => array('horde:administration:cmdshell')
));

$title = _("Command Shell");

$view = new Horde_View(array(
    'templatePath' => HORDE_TEMPLATES . '/admin'
));
$view->addHelper('Horde_Core_View_Helper_Help');
$view->addHelper('Text');

$view->action = Horde::url('admin/cmdshell.php');
$view->command = trim(Horde_Util::getFormData('cmd'));
$view->title = $title;

if ($view->command) {
    $cmds = explode("\n", $view->command);
    $out = array();

    foreach ($cmds as $cmd) {
        $cmd = trim($cmd);
        if (strlen($cmd)) {
            $out[] = shell_exec($cmd);
        }
    }

    $view->out = $out;
}

$page_output->header(array(
    'title' => $title
));
require HORDE_TEMPLATES . '/admin/menu.inc';
echo $view->render('cmdshell');
$page_output->footer();
