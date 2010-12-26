<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel');
try {
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery((int)Horde_Util::getFormData('gallery'));
} catch (Ansel_Exception $e) {
    echo $e->getMessage();
    Horde::logMessage($e->getMessage(), 'err');
    exit;
}
if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ)) {
    throw new Horde_Exception_PermissionDenied();
}

$from = (int)Horde_Util::getFormData('from');
$to = (int)Horde_Util::getFormData('to');
$count = $to - $from + 1;

$images = $gallery->getImages($from, $count);
foreach ($images as $image) {
    echo  '<li class="small">';
    echo '<div style="width:90px;">';
    $alt = htmlspecialchars($image->filename);
    echo '<img onclick="ansel_lb.start(' . $image->id . ')" src="' . Ansel::getImageUrl($image->id, 'mini') . '" alt="' . $alt . '" title="' . $alt . '" />';
    echo '</div></li>' . "\n";
}
