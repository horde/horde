<?php
/**
* Download and veiew files
*
 * Copyright 2006 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: files.php 183 2008-01-06 17:39:50Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */

/* application include */
$no_compress = true;
define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';

$id = Util::getFormData('id', false);
$actionID = Util::getFormData('actionID');
$filedir = Util::getFormData('dir');
$filename = Util::getFormData('file');
$type = substr($filename, strpos($filename, '.'));

/* Run through action handlers. */
switch ($actionID) {
case 'download_file':

    $browser->downloadHeaders($filename);
    readfile($conf['attributes']['attachments'] . $filedir . '/' . $filename);
    break;

case 'view_file':

    require_once 'Horde/MIME/Part.php';
    require_once 'Horde/MIME/Viewer.php';
    require_once 'Horde/MIME/Magic.php';
    require_once 'Horde/MIME/Contents.php';

    $data = file_get_contents($conf['attributes']['attachments'] . $filedir . '/' . $filename);
    $mime = &new MIME_Part(MIME_Magic::extToMIME($type), $data);
    $mime->setName($filename);
    $contents = &new MIME_Contents($mime);
    $body = $contents->renderMIMEPart($mime);
    $type = $contents->getMIMEViewerType($mime);
    $browser->downloadHeaders($mime->getName(true, true), $type, true, strlen($body));
    echo $body;

break;

case 'download_zip':

    if ($id) {
        $filename = sprintf(_("FilesOfNews-%s"), $id);
        $zipfiles = array();
        foreach ($news->getFiles($id) as $file_data) {
            $zipfiles[] = array('data' => file_get_contents($conf['attributes']['attachments'] . $file_data['filename']),
                                'name' => basename($file_data['filename']));
        }
    } else {
        $zipfiles = array('data' => file_get_contents($conf['attributes']['attachments'] . $filedir . '/' . $filename),
                            'name' => $filename);
    }

    $zip = Horde_Compress::singleton('zip');
    $body = $zip->compress($zipfiles);
    $browser->downloadHeaders($filename . '.zip', 'application/zip', false, strlen($body));
    echo $body;

break;
}

