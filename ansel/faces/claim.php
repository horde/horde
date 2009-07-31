<?php
/**
 * Identify a person in a photo
 *
 * $Horde: ansel/faces/claim.php,v 1.9 2009/06/10 00:33:02 mrubinsk Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
define('ANSEL_BASE', dirname(__FILE__) . '/../');
require_once ANSEL_BASE . '/lib/base.php';
require_once ANSEL_BASE . '/lib/Faces.php';

require_once 'Horde/Form.php';

$faces = Ansel_Faces::factory();

$face_id = Horde_Util::getFormData('face');
$face = $faces->getFaceById($face_id);

if (is_a($face, 'PEAR_Error')) {
    $notification->push($face->getMessage());
    header('Location: ' . Horde::applicationUrl('faces/search/all.php'));
    exit;
}

$title = _("Tell us who is in this photo");

$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, $title);
$form->addHidden('', 'face', 'int', true);
$form->addVariable(_("Person"), 'person', 'text', true);
$form->setButtons($title);
if ($form->validate()) {
    if (Horde_Util::getFormData('submitbutton') == _("Cancel")) {
        $notification->push(_("Action was cancelled."), 'horde.warning');
    } else {
        require ANSEL_BASE . '/lib/Report.php';
        $report = Ansel_Report::factory();
        $gallery = $ansel_storage->getGallery($face['gallery_id']);

        $face_link = Horde_Util::addParameter(
            Horde::applicationUrl('faces/custom.php', true),
            array('name' => $vars->get('person'),
                  'face' => $face_id,
                  'image' => $face['image_id']), null, false);

        $title = _("I know who is on one of your photos");
        $body = _("Gallery Name") . ': ' . $gallery->get('name') . "\n"
                . _("Gallery Description") . ': ' . $gallery->get('desc') . "\n\n"
                . $title . "\n"
                . _("Person") . ': ' . $vars->get('person') . "\n"
                . _("Face") . ': ' . $face_link;

        $report->setTitle($title);
        $result = $report->report($body, $gallery->get('owner'));
        if (is_a($result, 'PEAR_Error')) {
            $notification->push(_("Face name was not reported.") . ' ' .
                                $result->getMessage(), 'horde.error');
        } else {
            $notification->push(_("The owner of the photo, who will delegate the face name, was notified."), 'horde.success');
        }
    }

    header('Location: ' . Ansel_Faces::getLink($face));
    exit;
}

require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';

$form->renderActive(null, null, null, 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';