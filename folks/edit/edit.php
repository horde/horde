<?php
/**
 * $Id: edit.php 974 2008-10-07 19:46:00Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

define('FOLKS_BASE', dirname(__FILE__) . '/..');
require_once FOLKS_BASE . '/lib/base.php';
require_once 'tabs.php';

$title = _("Edit my profile");

$profile = $folks_driver->getRawProfile($GLOBALS['registry']->getAuth());
if ($profile instanceof PEAR_Error) {
    $notification->push($profile);
    Folks::getUrlFor('list', 'list')->redirect();
}

$form = new Horde_Form($vars, $title, 'editprofile');
$form->addVariable(_("Email"), 'user_email', 'email', true);
$form->addVariable(_("Birthday"), 'user_birthday', 'monthdayyear', false, false, null, array(date('Y')-90, date('Y')-10, '%Y%m%d', '%Y%m%d'));
$form->addVariable(_("Gender"), 'user_gender', 'enum', false, false, null, array(array(1 => _("Male"), 2 => _("Female")), _("--- Select ---")));
$form->addVariable(_("City"), 'user_city', 'text', false);
$v = &$form->addVariable(_("Country"), 'user_country', 'enum', false, false, null, array(Folks::getCountries()));
$v->setDefault('SI');
$form->addVariable(_("Homepage"), 'user_url', 'text', false);

if ($registry->hasMethod('video/listVideos')) {
    try {
        $result = $registry->call('video/listVideos', array(array('author' => $GLOBALS['registry']->getAuth()), 0, 100));
        $videos = array();
        foreach ($result as $video_id => $video) {
            $videos[$video_id] = $video['video_title'] . ' - ' . Folks::format_date($video['video_created']);
        }
        $video_link = '<a href="' .  $registry->link('video/edit') . '">' . _("Upload a new video") . '</a>';
        $form->addVariable(_("Video"), 'user_video', 'enum', false, false, $video_link, array($videos, _("--- Select ---")));
    } catch (Horde_Exception $e) {
        $notification->push($e);
    }
}

$form->addVariable(_("Description"), 'user_description', 'longtext', false, false, false);
$form->addVariable(_("Picture"), 'user_picture', 'image', false);
$form->setButtons(array(_("Save"), _("Delete picture")));

if ($form->validate()) {
    switch (Horde_Util::getFormData('submitbutton')) {

    case _("Save"):
        $form->getInfo(null, $info);
        $info['user_description'] = strip_tags($info['user_description']);
        $info['user_city'] = strip_tags($info['user_city']);
        $info['user_url'] = strip_tags($info['user_url']);
        $result = $folks_driver->saveProfile($info);
        if ($result instanceof PEAR_Error) {
            $notification->push($result);
        } else {
            $notification->push(_("Your data were successfully updated."), 'horde.success');
            if (empty($data['user_picture'])) {
                $folks_driver->logActivity(_("Updated his/her profile details."));
            } else {
                $folks_driver->logActivity(_("Updated his/her profile picture."));
            }
            Horde::url('edit/edit.php')->redirect();
        }
    break;

    case _("Delete picture"):
        $result = $folks_driver->deleteImage($GLOBALS['registry']->getAuth());;
        if ($result instanceof PEAR_Error) {
            $notification->push($result);
        } else {
            $notification->push(_("Your image was deleted successfully."), 'horde.success');
        }

    break;

    }
} elseif (!$form->isSubmitted()) {

    foreach ($profile as $key => $value) {
        if ($key != 'user_picture' && !empty($value)) {
            $vars->set($key, $value);
        }
    }

}

require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

echo $tabs->render('edit');
$form->renderActive(null, null, null, 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
