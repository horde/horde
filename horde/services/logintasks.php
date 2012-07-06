<?php
/**
 * Login tasks confirmation page.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('nologintasks' => true));

$vars = Horde_Variables::getDefaultVariables();

/* If no 'module' parameter passed in, die with an error. */
if (!($app = basename($vars->app))) {
    throw new Horde_Exception('Do not directly access logintasks.php.');
}

$registry->pushApp($app, array('logintasks' => false));

if (!($tasks = $injector->getInstance('Horde_Core_Factory_LoginTasks')->create($app))) {
    throw new Horde_Exception('The Horde_LoginTasks:: class did not load successfully.');
}

/* If we are through with tasks, this call will redirect to application. */
$confirmed = array();
if ($vars->logintasks_page) {
    foreach ($vars as $key => $val) {
        if ($val && (strpos($key, 'logintasks_confirm_') === 0)) {
            $confirmed[] = $key;
        }
    }
}

$tasks->runTasks(array(
    'confirmed' => $confirmed,
    'user_confirmed' => $vars->logintasks_page
));

/* Create the Horde_Template item. */
$template = $injector->createInstance('Horde_Template');

/* Have the maintenance module do all necessary processing. */
$tasklist = $tasks->displayTasks();
if (!is_array($tasklist)) {
    /* Probably should have redirected earlier. */
    $url = new Horde_Url($registry->getInitialPage());
    $url->redirect();
}

$app_name = $registry->get('name', 'horde');

switch ($tasklist[0]->display) {
case Horde_LoginTasks::DISPLAY_CONFIRM_NO:
case Horde_LoginTasks::DISPLAY_CONFIRM_YES:
    /* Confirmation-style output. */
    $template->set('confirm', true, true);
    $template->set('agree', false, true);
    $template->set('notice', false, true);

    $title = sprintf(_("%s Tasks - Confirmation"), $app_name);
    $header = sprintf(_("%s is ready to perform the tasks below. Select each operation to run at this time."), $app_name);
    break;

case Horde_LoginTasks::DISPLAY_AGREE:
    /* Agreement-style output. */
    $template->set('confirm', false, true);
    $template->set('agree', true, true);
    $template->set('notice', false, true);

    $title = sprintf(_("%s Terms of Agreement"), $app_name);
    $header = _("Please read the following text. You MUST agree with the terms to use the system.");
    break;

case Horde_LoginTasks::DISPLAY_NOTICE:
    /* Notice-style output. */
    $template->set('confirm', false, true);
    $template->set('agree', false, true);
    $template->set('notice', true, true);

    $title = sprintf(_("%s - Notice"), $app_name);
    $header = '';
    break;
}

/* Make variable array needed for templates. */
$display_tasks = array();
foreach ($tasklist as $key => $ob) {
    $display_tasks[] = array(
        'checked' => ($ob->display == Horde_LoginTasks::DISPLAY_CONFIRM_YES),
        'descrip' => $ob->describe(),
        'key' => $key
    );
}

$template->setOption('gettext', true);
$template->set('title', $title);
$template->set('header', $header);
$template->set('tasks', $display_tasks, true);
$template->set('logintasks_url', $tasks->getLoginTasksUrl());

$page_output->addScriptFile('logintasks.js', 'horde');
$page_output->header(array(
    'body_class' => 'modal-form',
    'body_id' => 'services_logintasks',
    'title' => $title,
    'view' => $registry->getView()
));

switch ($registry->getView()) {
case Horde_Registry::VIEW_SMARTMOBILE:
    echo $template->fetch(HORDE_TEMPLATES . '/logintasks/smartmobile.html');
    break;

default:
    echo $template->fetch(HORDE_TEMPLATES . '/logintasks/logintasks.html');
    break;
}

$page_output->footer();
