<?php
/**
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/base.php';

/* Abort if ecard sending is disabled. */
if (empty($conf['ecard']['enable'])) {
    exit;
}

/* Get the gallery and the image, and abort if either fails. */
$gallery = $ansel_storage->getGallery(Horde_Util::getFormData('gallery'));
if (is_a($gallery, 'PEAR_Error')) {
    exit;
}
$image = &$gallery->getImage(Horde_Util::getFormData('image'));
if (is_a($image, 'PEAR_Error')) {
    exit;
}

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

    $charset = Horde_Nls::getCharset();

    /* Create the text part. */
    $textpart = new Horde_Mime_Part();
    $textpart->setType('text/plain');
    $textpart->setCharset($charset);
    $textpart->setContents(_("You have been sent an Ecard. To view the Ecard, you must be able to view text/html messages in your mail reader. If you are viewing this message, then most likely your mail reader does not support viewing text/html messages."));

    /* Create the multipart/related part. */
    $related = new Horde_Mime_Part();
    $related->setType('multipart/related');

    /* Create the HTML part. */
    $htmlpart = new Horde_Mime_Part();
    $htmlpart->setType('text/html');
    $htmlpart->setCharset($charset);

    /* The image part */
    $imgpart = new Horde_Mime_Part();
    $imgpart->setType($image->getType('screen'));
    $imgpart->setContents($image->raw('screen'));
    $img_tag = '<img src="cid:' . $imgpart->setContentID() . '" /><p />';
    $comments = $htmlpart->replaceEOL(Horde_Util::getFormData('ecard_comments'));
    if (!Horde_Util::getFormData('rtemode')) {
        $comments = '<pre>' . htmlspecialchars($comments, ENT_COMPAT, Horde_Nls::getCharset()) . '</pre>';
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
    $alt = new Horde_Mime_Mail(array('subject' => _("Ecard - ") . Horde_Util::getFormData('image_desc'), 'to' => $to, 'from' => $from, 'charset' => $charset));
    $alt->setBasePart($alternative);

    /* Send. */
    $result = $alt->send(Horde::getMailerConfig());
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error sending your message: %s"), $result->getMessage()), 'horde.error');
    } else {
        Horde_Util::closeWindowJS();
        exit;
    }
}

$title = sprintf(_("Send Ecard :: %s"), $image->filename);

/* Set up the form object. */
$vars = Horde_Variables::getDefaultVariables();
$vars->set('actionID', 'send');
$vars->set('image_desc', strlen($image->caption) ? $image->caption : $image->filename);
$form = new Ansel_Form_Ecard($vars, $title);
$renderer = new Horde_Form_Renderer();

if ($browser->hasFeature('rte')) {
    $editor = Horde_Editor::factory('xinha', array('id' => 'ecard_comments'));
    $vars->set('rtemode', 1);
    $form->addHidden('', 'rtemode', 'text', false);
}

require ANSEL_TEMPLATES . '/common-header.inc';
$notification->notify(array('listeners' => 'status'));
$form->renderActive($renderer, $vars, 'ecard.php', 'post', 'multipart/form-data');
require $registry->get('templates', 'horde') . '/common-footer.inc';
