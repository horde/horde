<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('whups');

$actionID = Horde_Util::getFormData('actionID');
$id = Horde_Util::getFormData('ticket');
$filename = Horde_Util::getFormData('file');
$type = Horde_Util::getFormData('type');

// Get the ticket details first.
if (empty($id)) {
    exit;
}
$details = $whups_driver->getTicketDetails($id);
if ($details instanceof PEAR_Error) {
    if ($details->code === 0) {
        // No permissions to this ticket.
        Horde::url($registry->get('webroot', 'horde') . '/login.php', true)
            ->add('url', Horde::selfUrl(true))
            ->redirect();
    } else {
        throw new Horde_Exception($details);
    }
}

// Check permissions on this ticket.
if (!count(Whups::permissionsFilter($whups_driver->getHistory($id), 'comment', Horde_Perms::READ))) {
    throw new Horde_Exception(sprintf(_("You are not allowed to view ticket %d."), $id));
}

try {
    $vfs = $injector->getInstance('Horde_Vfs')->getVfs();
} catch (Horde_Exception $e) {
    throw new Horde_Exception(_("The VFS backend needs to be configured to enable attachment uploads."));
}

try {
    $data = $vfs->read(WHUPS_VFS_ATTACH_PATH . '/' . $id, $filename);
} catch (VFS_Exception $e) {
    throw Horde_Exception(sprintf(_("Access denied to %s"), $filename));
}

/* Run through action handlers */
switch ($actionID) {
case 'download_file':
     $browser->downloadHeaders($filename, null, false, strlen($data));
     echo $data;
     exit;

case 'view_file':
    $mime_part = new Horde_Mime_Part();
    $mime_part->setType(Horde_Mime_Magic::extToMime($type));
    $mime_part->setContents($data);
    $mime_part->setName($filename);

    $ret = $injector->getInstance('Horde_Mime_Viewer')->getViewer($mime_part)->render('full');
    reset($ret);
    $key = key($ret);

    if (strpos($ret[$key]['type'], 'text/html') !== false) {
        require WHUPS_BASE . '/templates/common-header.inc';
        echo $ret[$key]['data'];
        require $registry->get('templates', 'horde') . '/common-footer.inc';
    } else {
        $browser->downloadHeaders($ret[$key]['name'], $ret[$key]['type'], true, strlen($ret[$key]['data']));
        echo $ret[$key]['data'];
    }
    exit;
}
