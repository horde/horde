<?php
/**
 * Download and view files
 *
 * $Id: files.php 1241 2009-01-29 23:27:58Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */

$no_compress = true;
require_once dirname(__FILE__) . '/lib/base.php';

$news_id = Horde_Util::getFormData('news_id', false);
$actionID = Horde_Util::getFormData('actionID');
$file_id = Horde_Util::getFormData('file_id');
$file_name = Horde_Util::getFormData('file_name');
$news_lang = Horde_Util::getFormData('news_lang', News::getLang());
$file_type = Horde_Util::getFormData('file_type');
$file_size = Horde_Util::getFormData('file_size');

/* Run through action handlers. */
switch ($actionID) {
case 'download_file':
    $data = News::getFile($file_id);
    if ($data instanceof PEAR_Error) {
        if ($registry->isAdmin(array('permission' => 'news:admin'))) {
            throw new Horde_Exception_Prior($data);
        } else {
            header('HTTP/1.0 404 Not Found');
            echo '<h1>HTTP/1.0 404 Not Found</h1>';
        }
        exit;
    }

    $browser->downloadHeaders($file_name, $file_type, false, $file_size);
    echo $data;
    break;

case 'view_file':

    $data = News::getFile($file_id);
    if ($data instanceof PEAR_Error) {
        if ($registry->isAdmin(array('permission' => 'news:admin'))) {
            throw new Horde_Exception_Prior($data);
        } else {
            header('HTTP/1.0 404 Not Found');
            echo '<h1>HTTP/1.0 404 Not Found</h1>';
        }
        exit;
    }

    $mime_part = new Horde_Mime_Part();
    $mime_part->setName($file_id);
    $mime_part->setType($file_type);
    $mime_part->setContents($data);

    $viewer = $injector->getInstance('Horde_Mime_Viewer')->getViewer($mime_part);
    if ($viewer) {
        $render = $viewer->render('full');
        if (!empty($render)) {
            reset($render);
            $key = key($render);
            $browser->downloadHeaders($file_name, $render[$key]['type'], true, strlen($render[$key]['data']));
            echo $render[$key]['data'];
            exit;
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
        $data = News::getFile($file_id);
        if ($data instanceof PEAR_Error) {
            continue;
        }
        $zipfiles[] = array('data' => $file_path,
                            'name' => $file);
    }

    if (empty($zipfiles)) {
        exit;
    }

    $zip = Horde_Compress::factory('zip');
    $body = $zip->compress($zipfiles);
    $browser->downloadHeaders($news_id . '.zip', 'application/zip', false, strlen($body));
    echo $body;

break;

case 'download_zip':
    $data = News::getFile($file_id);
    if ($data instanceof PEAR_Error) {
        if ($registry->isAdmin(array('permission' => 'news:admin'))) {
            throw new Horde_Exception_Prior($data);
        } else {
            header('HTTP/1.0 404 Not Found');
            echo '<h1>HTTP/1.0 404 Not Found</h1>';
        }
        exit;
    }

    $zipfiles = array('data' => $data, 'name' => $file_id);

    $zip = Horde_Compress::factory('zip');
    $body = $zip->compress($zipfiles);
    $browser->downloadHeaders($file_id . '.zip', 'application/zip', false, strlen($body));
    echo $body;

break;
}

