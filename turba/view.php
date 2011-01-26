<?php
/**
 * Turba view.php.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('turba');

if ($conf['documents']['type'] == 'none') {
    throw new Turba_Exception(_("The VFS backend needs to be configured to enable attachment uploads."));
}

$source = Horde_Util::getFormData('source');
$key = Horde_Util::getFormData('key');
$actionID = Horde_Util::getFormData('actionID');
$filename = Horde_Util::getFormData('file');
$type = Horde_Util::getFormData('type');

/* Get the object. */
if (!isset($cfgSources[$source])) {
    throw new Turba_Exception(_("The contact you requested does not exist."));
}

$driver = $injector->getInstance('Turba_Factory_Driver')->create($source);
$object = $driver->getObject($key);

/* Check permissions. */
if (!$object->hasPermission(Horde_Perms::READ)) {
    throw new Turba_Exception(_("You do not have permission to view this contact."));
}

try {
    $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create('documents');
} catch (Horde_Exception $e) {
    throw new Turba_Exception($e);
}

try {
    $data = $vfs->read(TURBA_VFS_PATH . '/' . $object->getValue('__uid'), $filename);
} catch (VFS_Exception $e) {
    Horde::logMessage($e, 'ERR');
    throw new Turba_Exception(sprintf(_("Access denied to %s"), $filename));
}

/* Run through action handlers */
switch ($actionID) {
case 'download_file':
     $browser->downloadHeaders($filename);
     echo $data;
     exit;

case 'view_file':
    $mime_part = new Horde_Mime_Part();
    $mime_part->setType(Horde_Mime_Magic::extToMime($type));
    $mime_part->setContents($data);
    $mime_part->setName($filename);
    $mime_part->buildMimeIds();

    $content = $injector->getInstance('Horde_Core_Factory_MimeViewer')->create($mime_part)->render('full');
    $body = $content[1]['data'];

    $browser->downloadHeaders($filename, $content[1]['type'], true, strlen($body));
    echo $body;
    exit;
}
