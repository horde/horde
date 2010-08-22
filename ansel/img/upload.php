<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

$gallery_id = Horde_Util::getFormData('gallery');
try {
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($gallery_id);
} catch (Ansel_Exception $e) {
    $notification->push(sprintf(_("Gallery %s not found."), $gallery_id), 'horde.error');
    Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
    exit;
}

$page = Horde_Util::getFormData('page', 0);
$vars = Horde_Variables::getDefaultVariables();

$form = new Ansel_Form_Upload($vars, _("Upload photos"));
if ($form->validate($vars)) {
    $valid = true;
    $uploaded = 0;
    $form->getInfo($vars, $info);

    /* Remember the ids of the images we uploaded so we can autogen */
    $image_ids = array();
    for ($i = 0; $i <= $conf['image']['num_uploads'] + 1; ++$i) {
        if (empty($info['file' . $i]['file'])) {
            continue;
        }

        /* Save new image. */
        try {
            $GLOBALS['browser']->wasFileUploaded('file' . $i);
        } catch (Horde_Browser_Exception $e) {
            if (!empty($info['file' . $i]['error'])) {
                $notification->push(sprintf(_("There was a problem uploading the photo: %s"), $info['file' . $i]['error']), 'horde.error');
            } elseif (!filesize($info['file' . $i]['file'])) {
                $notification->push(_("The uploaded file appears to be empty. It may not exist on your computer."), 'horde.error');
            }
            $valid = false;
            continue;
        }


        /* Check for a compressed file. */
        if (in_array($info['file' . $i]['type'],
                     array('x-extension/zip',
                           'application/x-compressed',
                           'application/x-zip-compressed',
                           'application/zip')) ||
            Horde_Mime_Magic::filenameToMime($info['file' . $i]['name']) == 'application/zip') {

            /* See if we can use the zip extension for reading the file. */
            if (Horde_Util::extensionExists('zip')) {
                $zip = new ZipArchive();
                if ($zip->open($info['file' . $i]['file']) !== true) {
                    $notification->push(sprintf(_("There was an error processing the uploaded archive: %s"), $info['file' . $i]['file']), 'horde.error');
                    continue;
                }

                for ($z = 0; $z < $zip->numFiles; $z++) {
                    $zinfo = $zip->statIndex($z);

                    /* Skip some known metadata files. */
                    $len = strlen($zinfo['name']);
                    if ($zinfo['name'][$len - 1] == '/') {
                        continue;
                    }
                    if ($zinfo['name'] == 'Thumbs.db') {
                        continue;
                    }
                    if (strrpos($zinfo['name'], '.DS_Store') == ($len - 9)) {
                        continue;
                    }
                    if (strrpos($zinfo['name'], '.localized') == ($len - 10)) {
                        continue;
                    }
                    if (strpos($zinfo['name'], '__MACOSX/') !== false) {
                        continue;
                    }

                    $stream = $zip->getStream($zinfo['name']);
                    $zdata = stream_get_contents($stream);
                    if (!strlen($zdata)) {
                        $notification->push(sprintf(_("There was an error processing the uploaded archive: %s"), $zinfo['name']), 'horde.error');
                        break;
                    }

                    /* If we successfully got data, try adding the image */
                    try {
                        $image_id = $gallery->addImage(
                            array(
                                'image_filename' => $zinfo['name'],
                                'image_caption' => '',
                                'data' => $zdata)
                        );
                        ++$uploaded;
                        if ($conf['image']['autogen'] > count($image_ids)) {
                            $image_ids[] = $image_id;
                        }
                    } catch (Ansel_Exception $e) {
                        $notification->push(sprintf(_("There was a problem saving the photo: %s"), $image_id), 'horde.error');
                    }
                    unset($zdata);
                }

                $zip->close();
                unset($zip);
            } else {
                /* Read in the uploaded data. */
                $data = file_get_contents($info['file' . $i]['file']);

                /* Get the list of files in the zipfile. */
                try {
                    $zip = Horde_Compress::factory('zip');
                    $files = $zip->decompress($data, array('action' => Horde_Compress_Zip::ZIP_LIST));
                } catch (Horde_Exception $e) {
                    $notification->push(sprintf(_("There was an error processing the uploaded archive: %s"), $e->getMessage()), 'horde.error');
                    continue;
                }

                foreach ($files as $key => $zinfo) {
                    /* Skip some known metadata files. */
                    $len = strlen($zinfo['name']);
                    if ($zinfo['name'][$len - 1] == '/') {
                        continue;
                    }
                    if ($zinfo['name'] == 'Thumbs.db') {
                        continue;
                    }
                    if (strrpos($zinfo['name'], '.DS_Store') == ($len - 9)) {
                        continue;
                    }
                    if (strrpos($zinfo['name'], '.localized') == ($len - 10)) {
                        continue;
                    }
                    if (strpos($zinfo['name'], '__MACOSX/') !== false) {
                        continue;
                    }

                    try {
                        $zdata = $zip->decompress($data, array('action' => Horde_Compress_Zip::ZIP_DATA,
                                                               'info' => $files,
                                                               'key' => $key));
                    } catch (Horde_Exception $e) {
                        $notification->push(sprintf(_("There was an error processing the uploaded archive: %s"), $e->getMessage()), 'horde.error');
                        break;
                    }

                    /* If we successfully got data, try adding the image */
                    try {
                        $image_id = $gallery->addImage(
                            array(
                                'image_filename' => $zinfo['name'],
                                'image_caption' => '',
                                'data' => $zdata)
                        );
                        ++$uploaded;
                        if ($conf['image']['autogen'] > count($image_ids)) {
                            $image_ids[] = $image_id;
                        }
                    } catch (Ansel_Exception $e) {
                        $notification->push(sprintf(_("There was a problem saving the photo: %s"), $image_id), 'horde.error');
                    }
                    unset($zdata);
                }
                unset($zip);
                unset($data);
            }
        } else {
            /* Read in the uploaded data. */
            $data = file_get_contents($info['file' . $i]['file']);

            /* Try and make sure the image is in a recognizeable
             * format. */
            if (getimagesize($info['file' . $i]['file']) === false) {
                $notification->push(_("The file you uploaded does not appear to be a valid photo."), 'horde.error');
                continue;
            }

            /* Add the image to the gallery */
            $image_data = array('image_filename' => $info['file' . $i]['name'],
                                'image_caption' => $vars->get('image' . $i . '_desc'),
                                'image_type' => $info['file' . $i]['type'],
                                'data' => $data,
                                'tags' => (isset($info['image' . $i . '_tags']) ? explode(',', $info['image' . $i . '_tags']) : array()));
            try {
                $image_id = $gallery->addImage($image_data, (bool)$vars->get('image' . $i . '_default'));
                ++$uploaded;
                $image_ids[] = $image_id;
            } catch (Ansel_Exception $e) {
                $notification->push(sprintf(_("There was a problem saving the photo: %s"), $image_id->getMessage()), 'horde.error');
                $valid = false;
            }
            unset($data);
        }
    }

    /* Try to autogenerate some views and tell the user what happened. */
    if ($uploaded) {
        $cnt = count($image_ids);
        for ($i = 0; $i < $conf['image']['autogen'] && $cnt > $i; $i++) {
            $image_id = $image_ids[$i];
            $image = &$GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImage($image_id);
            $image->createView('screen');
            $image->createView('thumb');
            $image->createView('mini');
            unset($image);
        }

        // postupload hook if needed
        try {
            Horde::callHook('postupload', array($image_ids));
        } catch (Horde_Exception_HookNotSet $e) {}
        $notification->push(sprintf(ngettext("%d photo was uploaded.", "%d photos were uploaded.", $uploaded), $uploaded), 'horde.success');
    } elseif ($vars->get('submitbutton') != _("Cancel")) {
        $notification->push(_("You did not select any photos to upload."), 'horde.error');
    }

    if ($valid) {
        /* Return to the gallery view. */
        Ansel::getUrlFor('view',
                         array('gallery' => $gallery_id,
                               'slug' => $gallery->get('slug'),
                               'view' => 'Gallery',
                               'page' => $page),
                         true)->redirect();
        exit;
    }
}
///* Preview existing images */
if ($gallery->countImages() && $browser->hasFeature('javascript')) {
    $haveImages = true;
}

$breadcrumbs = Ansel::getBreadCrumbs($gallery);
$title = _("Add Photo");
require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
require ANSEL_TEMPLATES . '/image/upload.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
