<?php
/**
 * The Agora script merge two threads.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('agora');

/* Set up the messages object. */
list($forum_id, $message_id, $scope) = Agora::getAgoraId();
$messages = $injector->getInstance('Agora_Factory_Driver')->create($scope, $forum_id);
if ($messages instanceof PEAR_Error) {
    $notification->push($messages->getMessage(), 'horde.warning');
    Horde::url('forums.php', true)->redirect();
}

/* Get requested message, if fail then back to forums list. */
$message = $messages->getMessage($message_id);
if ($message instanceof PEAR_Error) {
    $notification->push(sprintf(_("Could not open the message. %s"), $message->getMessage()), 'horde.warning');
    Horde::url('forums.php', true)->redirect();
}

/* Check delete permissions */
if (!$messages->hasPermission(Horde_Perms::DELETE)) {
    $notification->push(sprintf(_("You don't have permission to delete messages in forum %s."), $forum_id), 'horde.warning');
    $url = Agora::setAgoraId($forum_id, $message_id, Horde::url('messages/index.php', true), $scope);
    header('Location: ' . $url);
    exit;
}

/* Get the form object. */
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, sprintf(_("Merge \"%s\" with another thread"), $message['message_subject']));
$form->setButtons(array(_("Merge"), _("Cancel")));
$form->addHidden('', 'agora', 'text', false);
$form->addHidden('', 'scope', 'text', false);

$action_submit = Horde_Form_Action::factory('submit');
$threads_list = array();
foreach ($messages->getThreads(0, false, 'message_subject', 0) as $thread) {
    $threads_list[$thread['message_id']] = $thread['message_subject'];
}

$v = &$form->addVariable(_("With Thread: "), 'new_thread_id', 'enum', true, false, null, array($threads_list));
$v->setAction($action_submit);
$v->setOption('trackchange', true);

// TODO: show message list on first page load too.
if ($vars->get('new_thread_id')) {
    $message_list = array();
    foreach ($messages->getThreads($vars->get('new_thread_id'), true) as $thread) {
        $message_list[$thread['message_id']] = $thread['message_subject'] . ' (' . $thread['message_author'] . ' ' . $thread['message_date'] . ')';
    }
    $form->addVariable(_("After Message: "), 'after_message_id', 'enum', true, false, null, array($message_list));
}

/* Validate the form. */
if ($form->validate()) {
    $form->getInfo($vars, $info);

    if ($vars->get('submitbutton') == _("Merge")) {
        $merge = $messages->mergeThread($message_id, $info['after_message_id']);
        if ($merge instanceof PEAR_Error) {
            $notification->push($merge->getMessage(), 'horde.error');
        } else {
            $notification->push(sprintf(_("Thread %s merged with thread %s after message %s."), $message_id, $info['new_thread_id'], $info['after_message_id']), 'horde.error');
            header('Location: ' . Agora::setAgoraId($forum_id, $info['new_thread_id'], Horde::url('messages/index.php', true), $scope));
            exit;
        }
    }
}

/* Template object. */
$view = new Agora_View();
$view->menu = Horde::menu();

Horde::startBuffer();
$form->renderActive(null, $vars, Horde::url('message/merge.php'), 'post');
$view->main = Horde::endBuffer();

$view->message_subject = $message['message_subject'];
$view->message_author = $message['message_author'];
$view->message_body = Agora_Driver::formatBody($message['body']);

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $view->render('main');
require $registry->get('templates', 'horde') . '/common-footer.inc';
