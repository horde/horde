<?php
/**
 * $Horde: ansel/img/upload_preview.php,v 1.9 2009/07/13 17:18:39 mrubinsk Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/base.php';

$gallery_id = (int)Horde_Util::getFormData('gallery');
$gallery = $ansel_storage->getGallery($gallery_id);
if (is_a($gallery, 'PEAR_Error') ||
    !$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_READ)) {
    die(sprintf(_("Gallery %s not found."), $gallery_id));
}

$from = (int)Horde_Util::getFormData('from');
$to = (int)Horde_Util::getFormData('to');
$count = $to - $from + 1;

$images = $gallery->getImages($from, $count);
if (is_a($images, 'PEAR_Error')) {
    die($images->getError());
}

foreach ($images as $image) {
    echo  '<li class="small">';
    echo '<div style="width:90px;">';
    $alt = htmlspecialchars($image->filename);
    echo '<img onclick="ansel_lb.start(' . $image->id . ')" src="' . Ansel::getImageUrl($image->id, 'mini') . '" alt="' . $alt . '" title="' . $alt . '" />';
    echo '</div></li>' . "\n";
}
