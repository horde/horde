<?php
/**
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('whups');

$id = Horde_Util::getFormData('ticket');
$filename = Horde_Util::getFormData('file');
$type = Horde_Util::getFormData('type');

// Get the ticket details first.
if (empty($id)) {
    exit;
}
try {
    $details = $whups_driver->getTicketDetails($id);
} catch (Horde_Exception_PermissionDenied $e) {
    // No permissions to this ticket.
    Horde::url($registry->get('webroot', 'horde') . '/login.php', true)
        ->add('url', Horde::selfUrl(true))
        ->redirect();
}

// Check permissions on this ticket.
if (!count(Whups::permissionsFilter($whups_driver->getHistory($id), 'comment', Horde_Perms::READ))) {
    throw new Horde_Exception(sprintf(_("You are not allowed to view ticket %d."), $id));
}

try {
    $vfs = $injector->getInstance('Horde_Core_Factory_Vfs')->create();
} catch (Horde_Exception $e) {
    throw new Horde_Exception(_("The VFS backend needs to be configured to enable attachment uploads."));
}

try {
    $data = $vfs->read(Whups::VFS_ATTACH_PATH . '/' . $id, $filename);
} catch (Horde_Vfs_Exception $e) {
    throw Horde_Exception(sprintf(_("Access denied to %s"), $filename));
}

$mime_part = new Horde_Mime_Part();
$mime_part->setType(Horde_Mime_Magic::extToMime($type));
$mime_part->setContents($data);
$mime_part->setName($filename);
// We don't know better.
$mime_part->setCharset('US-ASCII');

$ret = $injector->getInstance('Horde_Core_Factory_MimeViewer')->create($mime_part)->render('full');
reset($ret);
$key = key($ret);

if (empty($ret)) {
    $browser->downloadHeaders($filename, null, false, strlen($data));
    echo $data;
} elseif (strpos($ret[$key]['type'], 'text/html') !== false) {
    $page_output->header();
    echo $ret[$key]['data'];
    $page_output->footer();
} else {
    $browser->downloadHeaders($filename, $ret[$key]['type'], true, strlen($ret[$key]['data']));
    echo $ret[$key]['data'];
}
