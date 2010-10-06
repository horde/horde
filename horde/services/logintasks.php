<?php
/**
 * Login tasks confirmation page.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('nologintasks' => true));

/* If no 'module' parameter passed in, die with an error. */
if (!($app = basename(Horde_Util::getFormData('app')))) {
    throw new Horde_Exception('Do not directly access logintasks.php.');
}

$registry->pushApp($app, array('logintasks' => false));

if (!($tasks = $injector->getInstance('Horde_Core_Factory_LoginTasks')->create($app))) {
    throw new Horde_Exception('The Horde_LoginTasks:: class did not load successfully.');
}

/* If we are through with tasks, this call will redirect to application. */
$tasks->runTasks(Horde_Util::getPost('logintasks_page'));

/* Create the Horde_Template item. */
$template = $injector->createInstance('Horde_Template');

/* Have the maintenance module do all necessary processing. */
$tasklist = $tasks->displayTasks();
$app_name = $registry->get('name', 'horde');

switch ($tasklist[0]->display) {
case Horde_LoginTasks::DISPLAY_CONFIRM_NO:
case Horde_LoginTasks::DISPLAY_CONFIRM_YES:
    /* Confirmation-style output. */
    $template->set('confirm', true, true);
    $template->set('agree', false, true);
    $template->set('notice', false, true);

    $notification->push(sprintf(_("%s is ready to perform the tasks checked below. Check the box for any operation(s) you want to perform at this time."), $app_name), 'horde.message');
    $template->set('header', sprintf(_("%s Tasks - Confirmation"), $app_name));
    break;

case Horde_LoginTasks::DISPLAY_AGREE:
    /* Agreement-style output. */
    $template->set('confirm', false, true);
    $template->set('agree', true, true);
    $template->set('notice', false, true);

    $notification->push(_("Please read the following text. You MUST agree with the terms to use the system."), 'horde.message');
    $template->set('header', sprintf(_("%s Terms of Agreement"), $app_name));
    break;

case Horde_LoginTasks::DISPLAY_NOTICE:
    /* Notice-style output. */
    $template->set('confirm', false, true);
    $template->set('agree', false, true);
    $template->set('notice', true, true);

    $template->set('header', sprintf(_("%s - Notice"), $app_name));
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
$template->set('tasks', $display_tasks, true);
$template->set('logintasks_url', $tasks->getLoginTasksUrl());

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

Horde::addScriptFile('logintasks.js', 'horde');

$bodyId = 'services_logintasks';
require HORDE_TEMPLATES . '/common-header.inc';
echo $template->fetch(HORDE_TEMPLATES . '/logintasks/logintasks.html');
require HORDE_TEMPLATES . '/common-footer.inc';
