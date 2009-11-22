<?php
/**
 * Turba view.php.
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/lib/base.php';

if ($conf['documents']['type'] == 'none') {
    throw new Horde_Exception(_("The VFS backend needs to be configured to enable attachment uploads."));
}

$source = Horde_Util::getFormData('source');
$key = Horde_Util::getFormData('key');
$actionID = Horde_Util::getFormData('actionID');
$filename = Horde_Util::getFormData('file');
$type = Horde_Util::getFormData('type');

/* Get the object. */
if (!isset($cfgSources[$source])) {
    throw new Horde_Exception(_("The contact you requested does not exist."));
}
$driver = Turba_Driver::singleton($source);
$object = $driver->getObject($key);
if (is_a($object, 'PEAR_Error')) {
    throw new Horde_Exception($object);
}

/* Check permissions. */
if (!$object->hasPermission(Horde_Perms::READ)) {
    throw new Horde_Exception(_("You do not have permission to view this contact."));
}

$v_params = Horde::getVFSConfig('documents');
if (is_a($v_params, 'PEAR_Error')) {
    throw new Horde_Exception($v_params);
}
$vfs = VFS::singleton($v_params['type'], $v_params['params']);
if (is_a($vfs, 'PEAR_Error')) {
    throw new Horde_Exception($vfs);
} else {
    $data = $vfs->read(TURBA_VFS_PATH . '/' . $object->getValue('__uid'), $filename);
}
if (is_a($data, 'PEAR_Error')) {
    Horde::logMessage($data, __FILE__, __LINE__, PEAR_LOG_ERR);
    throw new Horde_Exception(sprintf(_("Access denied to %s"), $filename));
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
    $viewer = Horde_Mime_Viewer::factory($mime_part);

    $content = $viewer->render('full');
    $body = $content[1]['data'];

    $browser->downloadHeaders($filename, $content[1]['type'], true, strlen($body));
    echo $body;
    exit;
}
