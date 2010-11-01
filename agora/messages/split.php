<?php
/**
 * The Agora script to split thread in two parts.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('agora');

/* Set up the messages object. */
list($forum_id, $message_id, $scope) = Agora::getAgoraId();
$messages = &Agora_Messages::singleton($scope, $forum_id);
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
$form = new Horde_Form($vars, sprintf(_("Split \"%s\""), $message['message_subject']));
$form->setButtons(array(_("Split"), _("Cancel")));
$form->addHidden('', 'agora', 'text', false);
$form->addHidden('', 'scope', 'text', false);

/* Validate the form. */
if ($form->validate()) {
    $form->getInfo($vars, $info);

    if ($vars->get('submitbutton') == _("Split")) {
        $split = $messages->splitThread($message_id);
        if ($split instanceof PEAR_Error) {
            $notification->push($split->getMessage(), 'horde.error');
        } else {
            $notification->push(sprintf(_("Thread splitted by message %s."), $message_id), 'horde.error');
            header('Location: ' . Agora::setAgoraId($forum_id, $message_id, Horde::url('messages/index.php', true), $scope));
            exit;
        }
    }
}

/* Template object. */
$view = new Agora_View();
$view->menu = Horde::menu();

Horde::startBuffer();
$form->renderActive(null, $vars, 'split.php', 'post');
$view->formbox = Horde::endBuffer();

$view->message_subject = $message['message_subject'];
$view->message_author = $message['message_author'];
$view->message_body = Agora_Messages::formatBody($message['body']);

require AGORA_TEMPLATES . '/common-header.inc';
echo $view->render('messages/edit.html.php');
require $registry->get('templates', 'horde') . '/common-footer.inc';
