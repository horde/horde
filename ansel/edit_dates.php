<?php
/**
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

$images = Horde_Util::getFormData('image', array());
$actionID = Horde_Util::getFormData('actionID');
$gallery_id = Horde_Util::getFormData('gallery');
$page = Horde_Util::getFormData('page', 0);

/* If we have a single gallery, check perms now */
if (!empty($gallery_id)) {
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($gallery_id);
    if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(_("You are not allowed to edit these photos."), 'horde.error');
        echo Horde::wrapInlineScript(array(
            'window.opener.location.href = window.opener.location.href;',
            'window.close();'
        ));
        exit;
    }
}

/* Make sure we have at least one image */
if (!count($images)) {
    echo $notification->push(_("You must select at least on photo to edit."), 'horde.error');
    echo Horde::wrapInlineScript(array(
        'window.opener.location.href = window.opener.location.href;',
        'window.close();'
    ));
    exit;
}

/* Set up the form */
$vars = Horde_Variables::getDefaultVariables();
$form = new Ansel_Form_ImageDate($vars, _("Edit Dates"));
/* Are we doing the edit now? */
if ($actionID == 'edit_dates') {
    $count = 0;
    foreach (array_keys($images) as $image_id) {
        try {
            $image = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImage($image_id);
            if (empty($gallery_id)) {
                // Images might be from different galleries
                $gallery = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($image->gallery);
                if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
                    continue;
                }
            }
            $newDate = new Horde_Date($vars->get('image_originalDate'));
            $image->originalDate = (int)$newDate->timestamp();
            $image->save();
            ++$count;
        } catch (Ansel_Exception $e) {
           $notification->push(sprintf(_("There was an error editing the dates: %s"), $e->getMessage()), 'horde.error');
            echo Horde::wrapInlineScript(array(
                'window.opener.location.href = window.opener.location.href;',
                'window.close();'
            ));
           exit;
        }
    }

    $notification->push(sprintf(_("Successfully modified the date on %d photos."), $count), 'horde.success');
    echo Horde::wrapInlineScript(array(
        'window.opener.location.href = window.opener.location.href;',
        'window.close();'
    ));
    exit;
}

$keys = array_keys($images);
$html = '';
foreach ($keys as $key) {
    $html .= '<img src="' . Ansel::getImageUrl($key, 'mini', false) . '" style="margin:2px;" alt="[thumbnail]" />';
}
$image = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImage(array_pop($keys));
/* Display the form */
$vars->set('image', $images);
$vars->set('gallery', $gallery_id);
$vars->set('page', $page);
$vars->set('actionID', 'edit_dates');
$vars->set('image_list', $html);
$vars->set('image_originalDate', $image->originalDate);
$renderer = new Horde_Form_Renderer();
$count = count($images);
include ANSEL_TEMPLATES . '/common-header.inc';
$form->renderActive($renderer, $vars, null, 'post');
// Needed to ensure the body element is large enough to hold the pop up calendar
echo '<br /><br /><br />';
require $registry->get('templates', 'horde') . '/common-footer.inc';
