<?php
/**
 * Gollem view script.
 *
 * Copyright 1999-2011 Horde LLC (http://www.horde.org/)
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

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array(
    'session_control' => 'readonly'
));

$vars = Horde_Variables::getDefaultVariables();

if ($vars->driver != Gollem::$backend['driver']) {
    Horde::url('login.php')->add(array(
        'backend_key' => $vars->driver,
        'url' => Horde::selfUrl(true)
    ))->redirect();
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

/* Run through action handlers. */
switch ($vars->actionID) {
case 'download_file':
    $browser->downloadHeaders($vars->file, null, false, $gollem_vfs->size($vars->dir, $vars->file));
    if (is_resource($stream)) {
        while ($buffer = fread($stream, 8192)) {
            echo $buffer;
            ob_flush();
            flush();
        }
    } else {
        echo $data;
    }
    break;

case 'view_file':
    if (is_resource($stream)) {
        $data = '';
        while ($buffer = fread($stream, 102400)) {
            $data .= $buffer;
        }
    }
    $mime = new Horde_Mime_Part();
    // TODO
    exit;

    $mime->setName($vars->name);
    $contents = new MIME_Contents($mime);
    $body = $contents->renderMIMEPart($mime);
    $type = $contents->getMIMEViewerType($mime);
    $browser->downloadHeaders($mime->getName(true, true), $type, true, strlen($body));
    echo $body;
    break;
}
