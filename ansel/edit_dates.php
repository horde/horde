<?php
/**
 * $Horde: ansel/edit_dates.php,v 1.8 2009/07/13 17:18:38 mrubinsk Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

@define('ANSEL_BASE', dirname(__FILE__));
require_once ANSEL_BASE . '/lib/base.php';

$images = Horde_Util::getFormData('image', array());
$actionID = Horde_Util::getFormData('actionID');
$gallery_id = Horde_Util::getFormData('gallery');
$page = Horde_Util::getFormData('page', 0);

/* If we have a single gallery, check perms now */
if (!empty($gallery_id)) {
    $gallery = $ansel_storage->getGallery($gallery_id);
    if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
        $notification->push(_("You are not allowed to edit these photos."), 'horde.error');
        Horde_Util::closeWindowJS('window.opener.location.href = window.opener.location.href; window.close();');
        exit;
    }
} else {
    // TODO - right now we should *always* have a gallery_id. If we get here
    //        from a results view, we may not, but that's not implemented yet.
}

/* Make sure we have at least one image */
if (!count($images)) {
    echo $notification->push(_("You must select at least on photo to edit."), 'horde.error');
    Horde_Util::closeWindowJS('window.opener.location.href = window.opener.location.href; window.close();');
    exit;
}

/* Includes */
require_once ANSEL_BASE . '/lib/Forms/ImageDate.php';
require_once 'Horde/Form/Renderer.php';

/* Set up the form */
$vars = Horde_Variables::getDefaultVariables();
$form = new ImageDateForm($vars, _("Edit Dates"));
/* Are we doing the edit now? */
if ($actionID == 'edit_dates') {
    $count = 0;
    foreach (array_keys($images) as $image_id) {
        $image = $ansel_storage->getImage($image_id);
        if (!is_a($image, 'PEAR_Error')) {
            if (empty($gallery_id)) {
                // Images might be from different galleries
                $gallery = $ansel_storage->getGallery($image->gallery);
                if (is_a($gallery, 'PEAR_Error') ||
                    !$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
                    continue;
                }
            }
            require_once 'Horde/Date.php';
            $newDate = new Horde_Date($vars->get('image_originalDate'));
            $image->originalDate = (int)$newDate->timestamp();
            $image->save();
            ++$count;
        } else {
           $notification->push(sprintf(_("There was an error editing the dates: %s"), $image->getMessage()), 'horde.error');
           Horde_Util::closeWindowJS('window.opener.location.href = window.opener.location.href; window.close();');
           exit;
        }

    }

    $notification->push(sprintf(_("Successfully modified the date on %d photos."), $count), 'horde.success');
    Horde_Util::closeWindowJS('window.opener.location.href = window.opener.location.href; window.close();');
    exit;
}

$keys = array_keys($images);
$html = '';
foreach ($keys as $key) {
    $html .= '<img src="' . Ansel::getImageUrl($key, 'mini', false) . '" style="margin:2px;" alt="[thumbnail]" />';
}
$image = $ansel_storage->getImage(array_pop($keys));
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
