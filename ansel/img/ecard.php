<?php
/**
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

/* Abort if ecard sending is disabled. */
if (empty($conf['ecard']['enable'])) {
    exit;
}

/* Get the gallery and the image, and abort if either fails. */
$gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery(Horde_Util::getFormData('gallery'));
$image = $gallery->getImage(Horde_Util::getFormData('image'));

/* Run through the action handlers. */
switch (Horde_Util::getFormData('actionID')) {
case 'send':
    /* Check for required elements. */
    $from = Horde_Util::getFormData('ecard_retaddr');
    if (empty($from)) {
        $notification->push(_("You must enter your e-mail address."), 'horde.error');
        break;
    }
    $to = Horde_Util::getFormData('ecard_addr');
    if (empty($to)) {
        $notification->push(_("You must enter an e-mail address to send the message to."), 'horde.error');
        break;
    }

    /* Create the text part. */
    $textpart = new Horde_Mime_Part();
    $textpart->setType('text/plain');
    $textpart->setCharset('UTF-8');
    $textpart->setContents(_("You have been sent an Ecard. To view the Ecard, you must be able to view text/html messages in your mail reader. If you are viewing this message, then most likely your mail reader does not support viewing text/html messages."));

    /* Create the multipart/related part. */
    $related = new Horde_Mime_Part();
    $related->setType('multipart/related');

    /* Create the HTML part. */
    $htmlpart = new Horde_Mime_Part();
    $htmlpart->setType('text/html');
    $htmlpart->setCharset('UTF-8');

    /* The image part */
    $imgpart = new Horde_Mime_Part();
    $imgpart->setType($image->getType('screen'));
    $imgpart->setContents($image->raw('screen'));
    $img_tag = '<img src="cid:' . $imgpart->setContentID() . '" /><p />';
    $comments = $htmlpart->replaceEOL(Horde_Util::getFormData('ecard_comments'));
    if (!Horde_Util::getFormData('rtemode')) {
        $comments = '<pre>' . htmlspecialchars($comments, ENT_COMPAT, 'UTF-8') . '</pre>';
    }
    $htmlpart->setContents('<html>' . $img_tag . $comments . '</html>');
    $related->setContentTypeParameter('start', $htmlpart->setContentID());
    $related->addPart($htmlpart);
    $related->addPart($imgpart);

    /* Create the multipart/alternative part. */
    $alternative = new Horde_Mime_Part();
    $alternative->setType('multipart/alternative');
    $alternative->addPart($textpart);
    $alternative->addPart($related);

    /* Add them to the mail message */
    $alt = new Horde_Mime_Mail(array('subject' => _("Ecard - ") . Horde_Util::getFormData('image_desc'), 'to' => $to, 'from' => $from, 'charset' => 'UTF-8'));
    $alt->setBasePart($alternative);

    /* Send. */
    try {
        $result = $alt->send($injector->getInstance('Horde_Mail'));
    } catch (Horde_Mime_Exception $e) {
        $notification->push(sprintf(_("There was an error sending your message: %s"), $e->getMessage()), 'horde.error');
    }
    echo Horde::wrapInlineScript(array('window.close();'));
    exit;
}

$title = sprintf(_("Send Ecard :: %s"), $image->filename);

/* Set up the form object. */
$vars = Horde_Variables::getDefaultVariables();
$vars->set('actionID', 'send');
$vars->set('image_desc', strlen($image->caption) ? $image->caption : $image->filename);
$form = new Ansel_Form_Ecard($vars, $title);
$renderer = new Horde_Form_Renderer();

$editor = $injector->getInstance('Horde_Editor')->initialize(array('id' => 'ecard_comments'));
if ($editor->supportedByBrowser()) {
    $vars->set('rtemode', 1);
    $form->addHidden('', 'rtemode', 'text', false);
}

require $registry->get('templates', 'horde') . '/common-header.inc';
$notification->notify(array('listeners' => 'status'));
$form->renderActive($renderer, $vars, 'ecard.php', 'post', 'multipart/form-data');
require $registry->get('templates', 'horde') . '/common-footer.inc';
