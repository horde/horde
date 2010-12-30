<?php
/**
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Rubinsky <mrubinsk@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

$imageId = Horde_Util::getFormData('image');
try {
    $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage($imageId);
    $gal = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery(abs($image->gallery));
    $img = Ansel::getImageUrl($imageId, 'thumb', false, Ansel::getStyleDefinition('ansel_default'));
} catch (Ansel_Exception $e) {
    Horde::logMessage($e->getMessage(), 'ERR');
    exit;
}
if ($gal->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW) &&
    !$gal->hasPasswd() &&
    $gal->isOldEnough()) {

    echo '<img src="' . $img . '" alt="' . htmlspecialchars($image->filename) . '">';
} else {
    echo '';
}
