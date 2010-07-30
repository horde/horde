<?php
/**
 * Process an single image (to be called by ajax)
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

$face_id = Horde_Util::getFormData('face');

$faces = $GLOBALS['injector']->getInstance('Ansel_Faces');
try {
    $face = $faces->getFaceById($face_id);
} catch (Horde_Exception $e) {
    $notification->push($e->getMessage());
    Horde::applicationUrl('faces/search/all.php')->redirect();
    exit;
}

$title = _("Report face");

$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, $title);
$form->addHidden('', 'face', 'int', true);
$form->addVariable(_("Reason"), 'reason', 'longtext', true, false, _("Please describe the reasons. For example, you don't want to be mentioned etc..."));
$form->setButtons($title);

if ($form->validate()) {

    if (Horde_Util::getFormData('submitbutton') == _("Cancel")) {
        $notification->push(_("Action was cancelled."), 'horde.warning');
    } else {
        require ANSEL_BASE . '/lib/Report.php';
        $report = Ansel_Report::factory();
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($face['gallery_id']);

        $face_link = Horde::applicationUrl('faces/face.php', true)->add(
                array('name' => $vars->get('person'),
                      'face' => $face_id,
                      'image' => $face['image_id']))->setRaw(true);

        $body = _("Gallery Name") . ': ' . $gallery->get('name') . "\n"
                . _("Gallery Description") . ': ' . $gallery->get('desc') . "\n\n"
                . $title . "\n"
                . _("Reason") . ': ' . $vars->get('reason') . "\n"
                . _("Face") . ': ' . $face_link;

        $report->setTitle($title);
        try {
            $result = $report->report($body, $gallery->get('owner'));
        } catch (Horde_Exception $e) {
            $notification->push(sprintf(_("Face name was not reported: %s"), $e->getMessage()), 'horde.error');
        }
        $notification->push(_("The owner of the photo was notified."), 'horde.success');
    }

    Ansel_Faces::getLink($face)->redirect();
    exit;
}

require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';

$form->renderActive(null, null, null, 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
