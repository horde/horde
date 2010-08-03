<?php
/**
 * The Agora script move thread another forum.
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
$form = new Horde_Form($vars, sprintf(_("Move \"%s\" to another forum"), $message['message_subject']));
$form->setButtons(array(_("Move"), _("Cancel")));
$form->addHidden('', 'agora', 'text', false);
$form->addHidden('', 'scope', 'text', false);

$forums_list = Agora::formatCategoryTree($messages->getForums(0, false));
$v = &$form->addVariable(_("Forum"), 'new_forum_id', 'enum', true, false, null, array($forums_list));
$v->setDefault($forum_id);

/* Validate the form. */
if ($form->validate()) {
    $form->getInfo($vars, $info);

    if ($vars->get('submitbutton') == _("Move")) {
        $move = $messages->moveThread($message_id, $info['new_forum_id']);
        if ($move instanceof PEAR_Error) {
            $notification->push($move->getMessage(), 'horde.error');
        } else {
            $notification->push(sprintf(_("Thread %s moved to from forum %s to %s."), $message_id, $forum_id, $info['new_forum_id']), 'horde.success');
            header('Location: ' . Agora::setAgoraId($info['new_forum_id'], $message_id, Horde::applicationUrl('messages/index.php', true), $scope));
            exit;
        }
    }
}

/* Template object. */
$view = new Agora_View();
$view->menu = Agora::getMenu('string');

Horde::startBuffer();
$form->renderActive(null, $vars, 'move.php', 'post');
$view->formbox = Horde::endBuffer();

$view->message_subject = $message['message_subject'];
$view->message_author = $message['message_author'];
$view->message_body = Agora_Messages::formatBody($message['body']);

require AGORA_TEMPLATES . '/common-header.inc';
echo $view->render('messages/edit.html.php');
require $registry->get('templates', 'horde') . '/common-footer.inc';
