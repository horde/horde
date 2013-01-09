<?php
/**
 * Copyright 2005-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Ben Chavet <ben@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('trean', array('session_control' => 'readonly'));

$bookmark_id = Horde_Util::getFormData('bookmark_id');
if (!$bookmark_id) {
    exit;
}

$bookmark = $trean_gateway->getBookmark($bookmark_id);
if (!$bookmark || !$bookmark->favicon_url) {
    exit;
}
$favicon_hash = md5($bookmark->favicon_url);

// Initialize VFS
try {
    $vfs = $GLOBALS['injector']
        ->getInstance('Horde_Core_Factory_Vfs')
        ->create();
    if (!$vfs->exists('.horde/trean/favicons/', $favicon_hash)) {
        exit;
    }
} catch (Exception $e) {
}

$data = $vfs->read('.horde/trean/favicons/', $favicon_hash);
$browser->downloadHeaders('favicon', null, true, strlen($data));
header('Expires: ' . gmdate('r', time() + 172800));
header('Cache-Control: public, max-age=172800');
header('Pragma:');
echo $data;
