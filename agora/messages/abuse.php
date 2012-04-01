<?php
/**
 * The Agora script to notify moderators of a abuse
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/../lib/Application.php';
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

/* We have any moderators? */
$forum = $messages->getForum();
if (!isset($forum['moderators'])) {
    $notification->push(_("No moderators are associated with this forum."), 'horde.warning');
    $url = Agora::setAgoraId($forum_id, $message_id, Horde::url('messages/index.php', true), $scope);
    header('Location: ' . $url);
    exit;
}

/* Get the form object. */
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, _("Report as abuse"));
$form->setButtons(array(_("Report as abuse"), _("Cancel")));
$form->addHidden('', 'agora', 'text', false);
$form->addHidden('', 'scope', 'text', false);

if ($form->validate()) {

    $url = Agora::setAgoraId($forum_id, $message_id, Horde::url('messages/index.php', true), $scope);

    if ($vars->get('submitbutton') == _("Cancel")) {
        header('Location: ' . $url);
        exit;
    }

    /* Collect moderators emails, and send them the notify */
    $emails = array();
    foreach ($forum['moderators'] as $moderator) {
        $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create($moderator);
        $address = $identity->getValue('from_addr');
        if (!empty($address)) {
            $emails[] = $address;
        }
    }

    if (empty($emails)) {
        header('Location: ' . $url);
        exit;
    }

    $mail = new Horde_Mime_Mail(array(
        'body' => $url . "\n\n" . $registry->getAuth() . "\n\n" . $_SERVER["REMOTE_ADDR"],
        'Subject' => sprintf(_("Message %s reported as abuse"),
                             $message_id),
        'To' => $emails,
        'From' => $emails[0],
        'User-Agent' => 'Agora ' . $registry->getVersion()));
    $mail->send($injector->getInstance('Horde_Mail'));

    $notification->push($subject, 'horde.success');
    header('Location: ' . $url);
    exit;
}

/* Set up template data. */
$view = new Agora_View();
$view->menu = Horde::menu();

Horde::startBuffer();
$form->renderActive(null, $vars, Horde::url('message/abuse.php'), 'post');
$view->formbox = Horde::endBuffer();

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$view->notify = Horde::endBuffer();

$view->message_subject = $message['message_subject'];
$view->message_author = $message['message_author'];
$view->message_date = strftime($prefs->getValue('date_format'), $message['message_timestamp']);
$view->message_body = Agora_Driver::formatBody($message['body']);

$page_output->header();
echo $view->render('messages/form');
$page_output->footer();
