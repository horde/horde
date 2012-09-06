<?php
/**
 * Nag smartmobile view.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('nag');


$title = _("My Tasklist");
$view = new Horde_View(array('templatePath' => NAG_TEMPLATES . '/smartmobile'));
$view->addHelper('Horde_Core_Smartmobile_View_Helper');
$view->addHelper('Text');

$page_output->addScriptFile('smartmobile.js');
require NAG_TEMPLATES . '/smartmobile/javascript_defs.php';

$page_output->header(array(
    'title' => _("My Tasks"),
    'view' => $registry::VIEW_SMARTMOBILE
));

// @TODO: Still need to refactor this to a dedicated smartmobile form.
// $max_tasks = $GLOBALS['injector']
//     ->getInstance('Horde_Core_Perms')
//     ->hasAppPermission('max_tasks');
// if (($max_tasks === true) || ($max_tasks > Nag::countTasks())) {
//     $vars = Horde_Variables::getDefaultVariables();
//     if (!$vars->exists('tasklist_id')) {
//         $vars->set('tasklist_id', Nag::getDefaultTasklist(Horde_Perms::EDIT));
//     }
//     $vars->mobile = true;
//     $vars->url = Horde::url('smartmobile.php');
//     $view->create_form = new Nag_Form_Task($vars, _("New Task"));
//     $view->create_title = $view->create_form->getTitle();
// }

echo $view->render('main');
// if ($view->create_form) {
//     echo $view->render('create');
// }

$page_output->footer();
