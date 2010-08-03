<?php
/**
 * The Agora script to delete a message.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('agora');

/* Set up the messages object. */
list($forum_id, $message_id, $scope) = Agora::getAgoraId();
$messages = &Agora_Messages::singleton($scope, $forum_id);
if ($messages instanceof PEAR_Error) {
    $notification->push($messages->getMessage(), 'horde.warning');
    Horde::applicationUrl('forums.php', true)->redirect();
}

/* Get requested message, if fail then back to forums list. */
$message = $messages->getMessage($message_id);
if ($message instanceof PEAR_Error) {
    $notification->push(sprintf(_("Could not open the message. %s"), $message->getMessage()), 'horde.warning');
    Horde::applicationUrl('forums.php', true)->redirect();
}

/* Check delete permissions */
if (!$messages->hasPermission(Horde_Perms::DELETE)) {
    $notification->push(sprintf(_("You don't have permission to delete messages in forum %s."), $forum_id), 'horde.warning');
    $url = Agora::setAgoraId($forum_id, $message_id, Horde::applicationUrl('messages/index.php', true), $scope);
    header('Location: ' . $url);
    exit;
}

/* Get the form object. */
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, sprintf(_("Delete \"%s\" and all replies?"), $message['message_subject']));
$form->setButtons(array(_("Delete"), _("Cancel")));
$form->addHidden('', 'agora', 'text', false);
$form->addHidden('', 'scope', 'text', false);

if ($form->validate()) {
    if ($vars->get('submitbutton') != _("Delete")) {
        $notification->push(_("Message not deleted."), 'horde.message');
        $url = Agora::setAgoraId($forum_id, $message_id, Horde::applicationUrl('messages/index.php', true), $scope);
        header('Location: ' . $url);
        exit;
    }

    $thread_id = $messages->deleteMessage($message_id);
    if ($thread_id instanceof PEAR_Error) {
        $notification->push(sprintf(_("Could not delete the message. %s"), $thread_id->getMessage()), 'horde.error');
    } elseif ($thread_id) {
        $notification->push(_("Message deleted."), 'horde.success');
        $url = Agora::setAgoraId($forum_id, $thread_id, Horde::applicationUrl('messages/index.php', true), $scope);
        header('Location: ' . $url);
        exit;
    } else {
        $notification->push(_("Thread deleted."), 'horde.success');
        $url = Agora::setAgoraId($forum_id, null, Horde::applicationUrl('threads.php', true), $scope);
        header('Location: ' . $url);
        exit;
    }
}

/* Set up template data. */
$view = new Agora_View();
$view->message_subject = $message['message_subject'];
$view->message_author = $message['message_author'];
$view->message_date = $messages->dateFormat($message['message_timestamp']);
$view->message_body = Agora_Messages::formatBody($message['body']);
$view->menu = Agora::getMenu('string');

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$view->notify = Horde::endBuffer();

Horde::startBuffer();
$form->renderActive(null, $vars, 'delete.php', 'post');
$view->formbox = Horde::endBuffer();

require AGORA_TEMPLATES . '/common-header.inc';
echo $view->render('messages/form.html.php');
require $registry->get('templates', 'horde') . '/common-footer.inc';
