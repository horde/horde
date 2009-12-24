<?php
/**
 * The Agora script to notify moderators of a abuse
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: agora/messages/abuse.php,v 1.24 2009/09/07 07:38:36 duck Exp $
 */

define('AGORA_BASE', dirname(__FILE__) . '/..');
require_once AGORA_BASE . '/lib/base.php';
require_once AGORA_BASE . '/lib/Messages.php';

/* Set up the messages object. */
list($forum_id, $message_id, $scope) = Agora::getAgoraId();
$messages = &Agora_Messages::singleton($scope, $forum_id);
if ($messages instanceof PEAR_Error) {
    $notification->push($messages->getMessage(), 'horde.warning');
    $url = Horde::applicationUrl('forums.php', true);
    header('Location: ' . $url);
    exit;
}

/* Get requested message, if fail then back to forums list. */
$message = $messages->getMessage($message_id);
if ($message instanceof PEAR_Error) {
    $notification->push(sprintf(_("Could not open the message. %s"), $message->getMessage()), 'horde.warning');
    header('Location: ' . Horde::applicationUrl('forums.php', true));
    exit;
}

/* We have any moderators? */
$forum = $messages->getForum();
if (!isset($forum['moderators'])) {
    $notification->push(_("No moderators are associated with this forum."), 'horde.warning');
    $url = Agora::setAgoraId($forum_id, $message_id, Horde::applicationUrl('messages/index.php', true), $scope);
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

    $url = Agora::setAgoraId($forum_id, $message_id, Horde::applicationUrl('messages/index.php', true), $scope);

    if ($vars->get('submitbutton') == _("Cancel")) {
        header('Location: ' . $url);
        exit;
    }

    /* Collect moderators emails, and send them the notify */
    require_once 'Horde/Identity.php';
    $emails = array();
    foreach ($forum['moderators'] as $moderator) {
        $identity = &Identity::singleton('none', $moderator);
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
        'subject' => sprintf(_("Message %s reported as abuse"),
                             $message_id),
        'body' => $url . "\n\n" . Horde_Auth::getAuth() . "\n\n" . $_SERVER["REMOTE_ADDR"],
        'to' => $emails,
        'from' => $emails[0],
        'charset' => Horde_Nls::getCharset()));
    $mail->addHeader('User-Agent', 'Agora ' . $registry->getVersion());
    $mail->send(Horde::getMailerConfig());

    $notification->push($subject, 'horde.success');
    header('Location: ' . $url);
    exit;
}

/* Set up template data. */
$view = new Agora_View();
$view->menu = Agora::getMenu('string');
$view->formbox = Horde_Util::bufferOutput(array($form, 'renderActive'), null, $vars, 'abuse.php', 'post');
$view->notify = Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status'));
$view->message_subject = $message['message_subject'];
$view->message_author = $message['message_author'];
$view->message_date = strftime($prefs->getValue('date_format'), $message['message_timestamp']);
$view->message_body = Agora_Messages::formatBody($message['body']);

require AGORA_TEMPLATES . '/common-header.inc';
echo $view->render('messages/form.html.php');
require $registry->get('templates', 'horde') . '/common-footer.inc';
