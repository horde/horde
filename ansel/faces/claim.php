<?php
/**
 * Identify a person in a photo
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

$faces = $GLOBALS['injector']->getInstance('Ansel_Faces');
$face_id = Horde_Util::getFormData('face');
try {
    $face = $faces->getFaceById($face_id);
} catch (Horde_Exception $e) {
    $notification->push($e->getMessage());
    Horde::url('faces/search/all.php')->redirect();
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
        $report = Ansel_Report::factory();
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($face['gallery_id']);

        $face_link = Horde::url('faces/custom.php', true)->add(
            array('name' => $vars->get('person'),
                  'face' => $face_id,
                  'image' => $face['image_id']))->setRaw(true);

        $title = _("I know who is on one of your photos");
        $body = _("Gallery Name") . ': ' . $gallery->get('name') . "\n"
                . _("Gallery Description") . ': ' . $gallery->get('desc') . "\n\n"
                . $title . "\n"
                . _("Person") . ': ' . $vars->get('person') . "\n"
                . _("Face") . ': ' . $face_link;

        $report->setTitle($title);
        try {
            $result = $report->report($body, $gallery->get('owner'));
            $notification->push(_("The owner of the photo, who will delegate the face name, was notified."), 'horde.success');
        } catch (Ansel_Exception $e) {
            $notification->push(_("Face name was not reported.") . ' ' . $e->getMessage(), 'horde.error');
        }
    }

    Ansel_Faces::getLink($face)->redirect();
    exit;
}

require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
$form->renderActive(null, null, null, 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
