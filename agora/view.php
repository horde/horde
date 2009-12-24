<?php
/**
 * Script to download attachments.
 *
 * $Horde: agora/view.php,v 1.14 2009/07/21 06:58:38 duck Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

define('AGORA_BASE', dirname(__FILE__));
require_once AGORA_BASE . '/lib/base.php';

$action_id = Horde_Util::getFormData('action_id', 'download');
$file_id = Horde_Util::getFormData('file_id');
$file_name = Horde_Util::getFormData('file_name');
$vfs_path = Agora::VFS_PATH . Horde_Util::getFormData('forum_id') . '/' . Horde_Util::getFormData('message_id');
$file_type = Horde_Util::getFormData('file_type');

/* Get VFS object. */
$vfs = Agora::getVFS();

/* Run through action handlers. TODO: Do inline viewing. */
switch ($action_id) {
case 'download':
    $file_data = $vfs->read($vfs_path, $file_id);
    $browser->downloadHeaders($file_name, $file_type, false, strlen($file_data));
    echo $file_data;
    break;
}
