<?php
/**
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
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

$style = Ansel::getStyleDefinition('ansel_default');
$style->thumbstyle = 'SquareThumb';
$style->width = 115;
$style->height = 115;
$from = (int)Horde_Util::getFormData('from');
$to = (int)Horde_Util::getFormData('to');
$count = $to - $from + 1;
$images = $gallery->getImages($from, $count);
foreach ($images as $image) {
    echo  '<li>';
    echo '<div>';
    $alt = htmlspecialchars($image->filename);
    echo '<img src="' . Ansel::getImageUrl($image->id, 'thumb', false, $style) . '" alt="' . $alt . '" title="' . $alt . '" />';
    echo '</div></li>' . "\n";
}
