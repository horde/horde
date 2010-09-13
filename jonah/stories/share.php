<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @package Jonah
 */

/**
 *
 * @global <type> $conf
 * @param <type> $story_part
 * @param <type> $from
 * @param <type> $recipients
 * @param <type> $subject
 * @param <type> $note
 * @return <type>
 */
function _mail($story_part, $from, $recipients, $subject, $note)
{
    global $conf;

    /* Create the MIME message. */
    $mail = new Horde_Mime_Mail(array('subject' => $subject,
                                      'to' => $recipients,
                                      'from' => $from,
                                      'charset' => $GLOBALS['registry']->getCharset()));
    $mail->addHeader('User-Agent', 'Jonah ' . $GLOBALS['registry']->getVersion());

    /* If a note has been provided, add it to the message as a text part. */
    if (strlen($note) > 0) {
        $message_note = new MIME_Part('text/plain', null, $GLOBALS['registry']->getCharset());
        $message_note->setContents($message_note->replaceEOL($note));
        $message_note->setDescription(_("Note"));
        $mail->addMIMEPart($message_note);
    }

    /* Get the story as a MIME part and add it to our message. */
    $mail->addMIMEPart($story_part);

    /* Log the pending outbound message. */
    Horde::logMessage(sprintf('<%s> is sending "%s" to (%s)',
                              $from, $subject, $recipients),
                      'INFO');

    /* Send the message and return the result. */
    return $mail->send(Horde::getMailerConfig());
}

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('jonah', array(
    'authentication' => 'none',
    'session_control' => 'readonly'
));

/* Set up the form variables. */
$vars = Horde_Variables::getDefaultVariables();
$channel_id = $vars->get('channel_id');
$story_id = $vars->get('id');

if (!$conf['sharing']['allow']) {
    Horde::url('stories/view.php', true)
        ->add(array('story_id' => $story_id, 'channel_id' => $channel_id))
        ->redirect();
    exit;
}

$story = $GLOBALS['injector']->getInstance('Jonah_Driver')->getStory($channel_id, $story_id);
if (is_a($story, 'PEAR_Error')) {
    $notification->push(sprintf(_("Error fetching story: %s"), $story->getMessage()), 'horde.warning');
    $story = '';
}
$vars->set('subject', $story['title']);

/* Set up the form. */
$form = new Horde_Form($vars);
$title = _("Share Story");
$form->setTitle($title);
$form->setButtons(_("Send"));
$form->addHidden('', 'channel_id', 'int', false);
$form->addHidden('', 'id', 'int', false);
$v = &$form->addVariable(_("From"), 'from', 'email', true, false);
if ($GLOBALS['registry']->getAuth()) {
    $v->setDefault($injector->getInstance('Horde_Prefs_Identity')->getIdentity()->getValue('from_addr'));
}
$form->addVariable(_("To"), 'recipients', 'email', true, false, _("Separate multiple email addresses with commas."), true);
$form->addVariable(_("Subject"), 'subject', 'text', true);
$form->addVariable(_("Include"), 'include', 'enum', true, false, null, array(array(_("A link to the story"), _("The complete text of the story"))));
$form->addVariable(_("Message"), 'message', 'longtext', false, false, null, array(4, 40));

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);

    $channel = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannel($channel_id);
    if (empty($channel['channel_story_url'])) {
<<<<<<< HEAD
        $story_url = Horde::url('stories/view.php', true);
        $story_url = Horde_Util::addParameter($story_url, array('channel_id' => '%c', 'id' => '%s'));
=======
        $story_url = Horde::url('stories/view.php', true);
        $story_url = Horde_Util::addParameter($story_url, array('channel_id' => '%c', 'story_id' => '%s'));
>>>>>>> master
    } else {
        $story_url = $channel['channel_story_url'];
    }

    $story_url = str_replace(array('%25c', '%25s'), array('%c', '%s'), $story_url);
    $story_url = str_replace(array('%c', '%s', '&amp;'), array($channel_id, $story['id'], '&'), $story_url);

    if ($info['include'] == 0) {
        require_once 'Horde/MIME/Part.php';

        /* TODO: Create a "URL link" MIME part instead. */
        $message_part = new MIME_Part('text/plain');
        $message_part->setContents($message_part->replaceEOL($story_url));
        $message_part->setDescription(_("Story Link"));
    } else {
        $message_part = Jonah::getStoryAsMessage($story);
    }

    $result = _mail($message_part, $info['from'], $info['recipients'],
                    $info['subject'], $info['message']);

    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("Unable to send story: %s"), $result->getMessage()), 'horde.error');
    } else {
        $notification->push(_("The story was sent successfully."), 'horde.success');
        header('Location: ' . $story_url);
        exit;
    }
}

$share_template = new Horde_Template();

// Buffer the form and notifications and send to the template
Horde::startBuffer();
$form->renderActive(null, $vars, 'share.php', 'post');
$share_template->set('main', Horde::endBuffer());

Horde::startBuffer();
$GLOBALS['notification']->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require JONAH_TEMPLATES . '/common-header.inc';
echo $share_template->fetch(JONAH_TEMPLATES . '/stories/share.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
