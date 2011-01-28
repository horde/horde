<?php
/**
 * The Agora script to post a new message, edit an existing message, or reply
 * to a message.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('agora');

list($forum_id, $message_id, $scope) = Agora::getAgoraId();
$message_parent_id = Horde_Util::getFormData('message_parent_id');

$vars = Horde_Variables::getDefaultVariables();
$vars->set('scope', $scope);
$formname = $vars->get('formname');

/* Set up the messages control object. */
$messages = &Agora_Messages::singleton($scope, $forum_id);
if ($messages instanceof PEAR_Error) {
    $notification->push(_("Could not post the message: ") . $messages->getMessage(), 'horde.warning');
    Horde::url('forums.php', true)->redirect();
}

/* Check edit permissions */
if (!$messages->hasPermission(Horde_Perms::EDIT)) {
    $notification->push(sprintf(_("You don't have permission to post messages in forum %s."), $forum_id), 'horde.warning');
    $url = Agora::setAgoraId($forum_id, $message_id, Horde::url('messages/index.php', true), $scope);
    header('Location: ' . $url);
    exit;
}

/* Check if a message is being edited. */
if ($message_id) {
    $message = $messages->getMessage($message_id);
    if (!$formname) {
        $vars = new Horde_Variables($message);
        $vars->set('message_subject', $message['message_subject']);
        $vars->set('message_body', $message['body']);
    }
    if ($message['attachments']) {
        $attachment_link = $messages->getAttachmentLink($message_id);
        if ($attachment_link) {
            $vars->set('attachment_preview', $attachment_link);
        }
    }
} else {
    $vars->set('forum_id', $forum_id);
    $vars->set('message_id', $message_id);
}

/* Get the forum details. */
$forum_name = $messages->_forum['forum_name'];

/* Set the title. */
$title = $message_parent_id ?
    sprintf(_("Post a Reply to \"%s\""), $forum_name) :
    ($message_id ? sprintf(_("Edit Message in \"%s\""), $forum_name) :
                   sprintf(_("Post a New Message to \"%s\""), $forum_name));

/* Get the form object. */
$form = $messages->getForm($vars, $title, $message_id);

/* Validate the form. */
if ($form->validate($vars)) {
    $form->getInfo($vars, $info);

    /* Try and store this message and get back a new message_id */
    $message_id = $messages->saveMessage($info);
    if ($message_id instanceof PEAR_Error) {
        $notification->push(_("Could not post the message: ") . $message_id->getDebugInfo(), 'horde.error');
    } else {
        if ($messages->_forum['forum_moderated']) {
            $notification->push(_("Your message has been enqueued and is awaiting moderation.  It will become visible after moderator approval."), 'horde.success');
        } else {
            $notification->push(_("Message posted."), 'horde.success');
        }
        if (!empty($info['url'])) {
            $url = Horde::url($info['url'], true);
        } else {
            $url = Agora::setAgoraId($forum_id, $message_id, Horde::url('messages/index.php', true), $scope);
        }
        header('Location: ' . $url);
        exit;
    }
}

/* Set up template */
$view = new Agora_View();

/* Check if a parent message exists and set up tags accordingly. */
if ($message_parent_id) {
    $message = $messages->replyMessage($message_parent_id);
    if (!($message instanceof PEAR_Error)) {
        $vars->set('message_subject', $message['message_subject']);
        $vars->set('message_body_old', $message['body']);
        $view->message_subject = $message['message_subject'];
        $view->message_author = $message['message_author'];
        $view->message_body = $message['body'];
    } else {
        /* Bad parent message id, offer to do a regular post. */
        $message_parent_id = null;
        $vars->set('message_parent_id', '');
        $notification->push(_("Invalid parent message, you will be posting this message as a new thread."), 'horde.warning');
    }
}

$view->replying = $message_parent_id;
$view->menu = Horde::menu();

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$view->notify = Horde::endBuffer();

Horde::startBuffer();
$form->renderActive(null, $vars, 'edit.php', 'post');
$view->formbox = Horde::endBuffer();

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $view->render('messages/edit.html.php');
require $registry->get('templates', 'horde') . '/common-footer.inc';
