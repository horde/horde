<?php
/**
 * Report offensive content
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Ansel
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

$title = _("Do you really want to report this gallery?");
$gallery_id = (int)Horde_Util::getFormData('gallery');
try {
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($gallery_id);
} catch (Ansel_Exception $e) {
    $notification->push($gallery->getMessage());
    Horde::url('view.php?view=List', true)->redirect();
    exit;
}

if (($image_id = Horde_Util::getFormData('image')) !== null) {
    $title = _("Do you really want to report this photo?");
    $return_url = Ansel::getUrlFor('view',
                                   array('view' => 'Image',
                                         'image' => $image_id,
                                         'gallery' => $gallery_id),
                                   true);
} else {
    $return_url = Ansel::getUrlFor('view',
                                   array('gallery' => $gallery_id,
                                         'view' => 'Gallery'),
                                   true);
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, $title);
$form->setButtons(array(_("Report"), _("Cancel")));

$enum = array('advertisement' => _("Advertisement content"),
              'terms' => _("Terms and conditions infringement"),
              'offensive' => _("Offensive content"),
              'copyright' => _("Copyright infringement"));

$form->addVariable($gallery->get('name'), 'name', 'description', false);
$form->addVariable($gallery->get('desc'), 'desc', 'description', false);

$form->addHidden('', 'gallery', 'text', true, true);
$vars->set('gallery', $gallery_id);

$form->addVariable(_("Report type"), 'type', 'radio', true, false, null, array($enum));
$form->addVariable(_("Report reason"), 'reason', 'longtext', true);

$gallery_id = Horde_Util::getFormData('id');

if ($form->validate()) {
    if (Horde_Util::getFormData('submitbutton') == _("Report")) {
        $report = Ansel_Report::factory();
        $body = _("Gallery Name") . ': ' . $gallery->get('name') . "\n"
            . _("Gallery Description") . ': ' . $gallery->get('desc') . "\n"
            . _("Gallery Id") . ': ' . $gallery->id . "\n"
            . _("Report type") . ': ' . $enum[$vars->get('type')] . "\n"
            . _("Report reason") . ': ' . $vars->get('reason') . "\n"
            . $return_url;

        try {
            $result = $report->report($body);
            $notification->push(_("Gallery was reported."), 'horde.success');
        } catch (Horde_Exception $e) {
            $notification->push(_("Gallery was not reported.") . ' ' . $result->getMessage(), 'horde.error');
        }
    } else {
        $notification->push(_("Gallery was not reported."), 'horde.warning');
    }
    $return_url->redirect();
    exit;
}

require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
$form->renderActive(null, null, null, 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
