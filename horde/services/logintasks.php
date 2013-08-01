<?php
/**
 * Login tasks confirmation page.
 *
 * Copyright 2001-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('nologintasks' => true));

$form_key = 'logintasks_confirm_';
$vars = $injector->getInstance('Horde_Variables');

/* If no 'module' parameter passed in, die with an error. */
if (!($app = basename($vars->app))) {
    throw new Horde_Exception('Do not directly access this script.');
}

$registry->pushApp($app, array('logintasks' => false));

if (!($tasks = $injector->getInstance('Horde_Core_Factory_LoginTasks')->create($app))) {
    throw new Horde_Exception('The Horde_LoginTasks class did not load successfully.');
}

/* If we are through with tasks, this call will redirect to application. */
$confirmed = array();
if ($vars->logintasks_page) {
    foreach ($vars as $key => $val) {
        if ($val && (strpos($key, $form_key) === 0)) {
            $confirmed[] = substr($key, strlen($form_key));
        }
    }
}

$tasks->runTasks(array(
    'confirmed' => $confirmed,
    'user_confirmed' => $vars->logintasks_page
));

$view = new Horde_View(array(
    'templatePath' => HORDE_TEMPLATES . '/logintasks'
));

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
    $view->confirm = true;
    $view->agree = false;
    $view->notice = false;

    $title = sprintf(_("%s Tasks - Confirmation"), $app_name);
    $header = sprintf(_("%s is ready to perform the tasks below. Select each operation to run at this time."), $app_name);
    break;

case Horde_LoginTasks::DISPLAY_AGREE:
    /* Agreement-style output. */
    $view->confirm = false;
    $view->agree = true;
    $view->notice = false;

    $title = sprintf(_("%s Terms of Agreement"), $app_name);
    $header = _("Please read the following text. You MUST agree with the terms to use the system.");
    break;

case Horde_LoginTasks::DISPLAY_NOTICE:
    /* Notice-style output. */
    $view->confirm = false;
    $view->agree = false;
    $view->notice = true;

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
        'name' => $form_key . $key
    );
}

$view->title = $title;
$view->header = $header;
$view->tasks = $display_tasks;
$view->logintasks_url = $tasks->getLoginTasksUrl();

switch ($registry->getView()) {
case Horde_Registry::VIEW_SMARTMOBILE:
    $page_output->addScriptFile('logintasks-jquery.js', 'horde');
    break;

default:
    $page_output->addScriptFile('logintasks.js', 'horde');
    break;
}

$page_output->topbar = $page_output->sidebar = false;

$page_output->header(array(
    'body_class' => 'modal-form',
    'body_id' => 'services_logintasks',
    'title' => $title,
    'view' => $registry->getView()
));

switch ($registry->getView()) {
case Horde_Registry::VIEW_SMARTMOBILE:
    echo $view->render('smartmobile');
    break;

default:
    echo $view->render('logintasks');
    break;
}

$page_output->footer();
