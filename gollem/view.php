<?php
/**
 * Gollem view script.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Max Kalika <max@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('gollem', array(
    'session_control' => 'readonly'
));

$vars = Horde_Variables::getDefaultVariables();

if ($vars->driver != Gollem::$backend['driver']) {
    Horde::url('login.php')->add(array(
        'backend_key' => $vars->driver,
        'url' => Horde::selfUrl(true)
    ))->redirect();
}

try {
    Gollem::changeDir();
} catch (Horde_Vfs_Exception $e) {
    $notification->push($e);
}

$gollem_vfs = $injector->getInstance('Gollem_Vfs');
$stream = null;
$data = '';
try {
    if (is_callable(array($gollem_vfs, 'readStream'))) {
        $stream = $gollem_vfs->readStream($vars->dir, $vars->file);
    } else {
        $data = $gollem_vfs->read($vars->dir, $vars->file);
    }
} catch (Horde_Vfs_Exception $e) {
    Horde::logMessage($e, 'NOTICE');
    throw $e;
}

$mime_part = new Horde_Mime_Part();
$mime_part->setType(Horde_Mime_Magic::extToMime($vars->type));
$mime_part->setContents(is_resource($stream) ? $stream : $data);
$mime_part->setName($vars->file);
// We don't know better.
$mime_part->setCharset('US-ASCII');

$ret = $injector
    ->getInstance('Horde_Core_Factory_MimeViewer')
    ->create($mime_part)
    ->render('full');
reset($ret);
$key = key($ret);
try {
    $size = $gollem_vfs->size($vars->dir, $vars->file);
} catch (Horde_Vfs_Exception $e) {
    $size = null;
}

if (empty($ret)) {
    $browser->downloadHeaders($vars->file, null, false, $size);
    if (is_resource($stream)) {
        fseek($stream, 0);
        while ($buffer = fread($stream, 8192)) {
            echo $buffer;
        }
    } else {
        echo $data;
    }
} elseif (strpos($ret[$key]['type'], 'text/html') !== false) {
    $page_output->header();
    echo $ret[$key]['data'];
    $page_output->footer();
} else {
    $browser->downloadHeaders($vars->file, $ret[$key]['type'], true, strlen($ret[$key]['data']));
    echo $ret[$key]['data'];
}
