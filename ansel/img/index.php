<?php
/**
 * $Horde: ansel/img/index.php,v 1.16 2009/07/08 18:28:42 slusarz Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/base.php';

$id = Horde_Util::getFormData('image');
$image = &$ansel_storage->getImage($id);
if (is_a($image, 'PEAR_Error')) {
    Horde::fatal($image, __FILE__, __LINE__);
}
$gallery = $ansel_storage->getGallery($image->gallery);
if (is_a($gallery, 'PEAR_Error')) {
    Horde::fatal($gallery, __FILE__, __LINE__);
}
if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_READ)) {
    Horde::fatal(_("Access denied viewing this photo."), __FILE__, __LINE__);
}

/* Sendfile support. Lighttpd < 1.5 only understands the X-LIGHTTPD-send-file header */
if ($conf['vfs']['src'] == 'sendfile') {
    $filename = $ansel_vfs->readFile($image->getVFSPath('screen'), $image->getVFSName('screen'));
    header('Content-Type: ' . $image->getType('screen'));
    header('X-LIGHTTPD-send-file: ' . $filename);
    header('X-Sendfile: ' . $filename);
    exit;
}

if (is_a($result = $image->display('screen'), 'PEAR_Error')) {
    Horde::fatal($result, __FILE__, __LINE__);
}
