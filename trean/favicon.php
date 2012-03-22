<?php
/**
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Ben Chavet <ben@horde.org>
 */

$session_control = 'readonly';
@define('TREAN_BASE', __DIR__);
require_once TREAN_BASE . '/lib/base.php';

$bookmark_id = Horde_Util::getFormData('bookmark_id');
if (!$bookmark_id) {
    exit;
}

$bookmark = &$trean_shares->getBookmark($bookmark_id);
if (!$favicon = $bookmark->favicon) {
    exit;
}

// Initialize VFS
$vfs_params = Horde::getVFSConfig('favicons');
if (is_a($vfs_params, 'PEAR_Error')) {
    exit;
}
$vfs = Horde_Vfs::factory($vfs_params['type'], $vfs_params['params']);

if (!$vfs->exists('.horde/trean/favicons/', $favicon)) {
    exit;
}

$data = $vfs->read('.horde/trean/favicons/', $favicon);
$browser->downloadHeaders('favicon', null, true, strlen($data));
header('Expires: ' . gmdate('r', time() + 172800));
header('Cache-Control: public, max-age=172800');
header('Pragma:');
echo $data;
