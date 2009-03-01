<?php
/**
 * Download and view files
 *
 * $Id: files.php 1241 2009-01-29 23:27:58Z duck $
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */

$no_compress = true;
require_once dirname(__FILE__) . '/lib/base.php';

$news_id = Util::getFormData('news_id', false);
$actionID = Util::getFormData('actionID');
$file_id = Util::getFormData('file_id');
$file_name = Util::getFormData('file_name');
$news_lang = Util::getFormData('news_lang', News::getLang());
$file_type = Util::getFormData('file_type');
$file_size = Util::getFormData('file_size');

/* Run through action handlers. */
switch ($actionID) {
case 'download_file':

    $browser->downloadHeaders($file_name, $file_type, false, $file_size);
    readfile($conf['attributes']['attachments'] . '/' . $file_id);
    break;

case 'view_file':

    $data = file_get_contents($conf['attributes']['attachments'] . '/' . $file_id);
    if ($data === false) {
        header('HTTP/1.0 404 Not Found');
        echo 'HTTP/1.0 404 Not Found';
        exit;
    }

    $mime_part = new Horde_Mime_Part();
    $mime_part->setName($file_id);
    $mime_part->setType($file_type);
    $mime_part->setContents($data);

    $viewer = Horde_Mime_Viewer::factory($mime_part);
    if ($viewer) {
        $render = $viewer->render('full');
        if (!empty($render)) {
            reset($render);
            $key = key($render);
            $browser->downloadHeaders($file_name, $render[$key]['type'], true, strlen($render[$key]['data']));
            echo $render[$key]['data'];
        }
    }

    // We cannnot see this file, so download it
    $browser->downloadHeaders($file_name, $file_type, false, $file_size);
    echo $data;

break;

case 'download_zip_all':

    $file_id = sprintf(_("FilesOfNews-%s"), $news_id);
    $zipfiles = array();
    foreach ($news->getFiles($news_id) as $file) {

        $file_path = $conf['attributes']['attachments'] . '/' . $file['file_id'];
        if (!file_exists($file_path)) {
            continue;
        }
        $zipfiles[] = array('data' => $file_path,
                            'name' => $file);
    }

    if (empty($zipfiles)) {
        exit;
    }

    $zip = Horde_Compress::singleton('zip');
    $body = @$zip->compress($zipfiles);
    $browser->downloadHeaders($news_id . '.zip', 'application/zip', false, strlen($body));
    echo $body;

break;

case 'download_zip':

    $zipfiles = array('data' => file_get_contents($conf['attributes']['attachments'] . '/' . $file_id),
                        'name' => $file_id);

    if ($zipfiles[0]['data'] === false) {
        header('HTTP/1.0 404 Not Found');
        echo 'HTTP/1.0 404 Not Found';
        exit;
    }

    $zip = Horde_Compress::singleton('zip');
    $body = @$zip->compress($zipfiles);
    $browser->downloadHeaders($file_id . '.zip', 'application/zip', false, strlen($body));
    echo $body;

break;
}

