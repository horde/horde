<?php
/**
 * Gollem view script.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Max Kalika <max@horde.org>
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => array('session_control' => 'readonly'));

$actionID = Horde_Util::getFormData('actionID');
$driver = Horde_Util::getFormData('driver');
$filedir = Horde_Util::getFormData('dir');
$filename = Horde_Util::getFormData('file');
$type = Horde_Util::getFormData('type');

if ($driver != $GLOBALS['gollem_be']['driver']) {
    Horde::url('login.php')
        ->add(array('backend_key' => $driver,
                    'change_backend' => 1,
                    'url' => Horde::selfURL(true)))
        ->redirect();
}

$stream = null;
$data = '';
try {
    if (is_callable(array($gollem_vfs, 'readStream'))) {
        $stream = $gollem_vfs->readStream($filedir, $filename);
    } else {
        $data = $gollem_vfs->read($filedir, $filename);
    }
} catch (VFS_Exception $e) {
    Horde::logMessage($e, 'NOTICE');
    throw $e;
}

/* Run through action handlers. */
switch ($actionID) {
case 'download_file':
    $browser->downloadHeaders($filename, null, false, $GLOBALS['gollem_vfs']->size($filedir, $filename));
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

    Horde_Mime_Magic::extToMIME($type), $data);
    $mime->setName($filename);
    $contents = new MIME_Contents($mime);
    $body = $contents->renderMIMEPart($mime);
    $type = $contents->getMIMEViewerType($mime);
    $browser->downloadHeaders($mime->getName(true, true), $type, true, strlen($body));
    echo $body;
    break;
}
