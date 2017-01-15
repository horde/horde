<?php
/**
 * Kronolith Mime viewer.
 *
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Jan Schneider <jan@horde.org>
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

$source = Horde_Util::getFormData('source');
$key = Horde_Util::getFormData('key');
$filename = Horde_Util::getFormData('file');
$type = Horde_Util::getFormData('type');

list($driver_type, $calendar) = explode('|', $source);
if ($driver_type == 'internal' &&
    !Kronolith::hasPermission($calendar, Horde_Perms::SHOW)) {
    $GLOBALS['notification']->push(_("Permission Denied"), 'horde.error');
    return false;
}

try {
    $driver = Kronolith::getDriver($driver_type, $calendar);
} catch (Exception $e) {
    $GLOBALS['notification']->push($e, 'horde.error');
    return false;
}
$event = $driver->getEvent($key);

/* Check permissions. */
if (!$event->hasPermission(Horde_Perms::READ)) {
    throw new Kronolith_Exception(_("You do not have permission to view this event."));
}

try {
    $data = $event->vfsInit()->read(Kronolith::VFS_PATH . '/' . $event->getVfsUid(), $filename);
} catch (Horde_Vfs_Exception $e) {
    Horde::log($e, 'ERR');
    throw new Kronolith_Exception(sprintf(_("Access denied to %s"), $filename));
}

$mime_part = new Horde_Mime_Part();
// $type might already be a mime_type.
if (strpos($type, '/')) {
    $mime_part->setType($type);
} else {
    $mime_part->setType(Horde_Mime_Magic::extToMime($type));
}
$mime_part->setContents($data);
$mime_part->setName($filename);
$mime_part->buildMimeIds();

$content = $injector->getInstance('Horde_Core_Factory_MimeViewer')->create($mime_part)->render('full');
$body = $content[1]['data'];
$browser->downloadHeaders($filename, $content[1]['type'], true, strlen($body));
echo $body;
