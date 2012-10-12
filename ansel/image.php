<?php
/**
 * Responsible for making changes to image properties as well as making,
 * previewing and saving changes to the image.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ansel');

// Get all the form data
$actionID = Horde_Util::getFormData('actionID');
$gallery_id = Horde_Util::getFormData('gallery');
$image_id = Horde_Util::getFormData('image');
$page = Horde_Util::getFormData('page', 0);
$watermark_font = Horde_Util::getFormData('font');
$watermark_halign = Horde_Util::getFormData('whalign');
$watermark_valign = Horde_Util::getFormData('wvalign');
$watermark = Horde_Util::getFormData('watermark', $prefs->getValue('watermark'));
$date = Ansel::getDateParameter();

// Are we watermarking the image?
if ($watermark) {
    $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();
    $name = $identity->getValue('fullname');
    if (empty($name)) {
        $name = $registry->getAuth();
    }

    // Set up array of possible substitutions.
    $watermark_array = array(
        '%N' => $name,
        '%L' => $registry->getAuth());
    $watermark = str_replace(
        array_keys($watermark_array),
        array_values($watermark_array), $watermark);
    $watermark = strftime($watermark);
}

// See if any tags were passed in to add (when js not present)
$tags = Horde_Util::getFormData('addtag');

// Redirect to the image list if no other action has been requested.
if (is_null($actionID) && is_null($tags)) {
    Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
    exit;
}

// Get the gallery object and style information.
try {
    $gallery = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->getGallery($gallery_id);
} catch (Ansel_Exception $e) {
    $notification->push(
        sprintf(_("Gallery %s not found."), $gallery_id), 'horde.error');
    Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
    exit;
}

// Do we have tags to update?
if (!is_null($tags) && strlen($tags)) {
    $tags = explode(',', $tags);
    if (!empty($image_id)) {
        $resource = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getImage($image_id);
    } else {
        $resource = $gallery;
    }
    $resource->setTags($tags, false);

    // If no other action requested, redirect back to the appropriate view
    if (empty($actionID)) {
        if (empty($image_id)) {
            $url = Ansel::getUrlFor(
                'view',
                array_merge(
                    array(
                        'view' => 'Gallery',
                        'gallery' => $gallery_id,
                        'slug' => $gallery->get('slug')
                    ),
                    $date
                ),
                true
            );
        } else {
            $url = Ansel::getUrlFor(
                'view',
                array_merge(
                    array(
                        'view' => 'Image',
                        'gallery' => $gallery_id,
                        'image' => $image_id,
                        'slug' => $gallery->get('slug')
                    ),
                    $date
                ),
               true
            );
        }
        $url->redirect();
        exit;
    }
}

// Run through the action handlers.
switch ($actionID) {
case 'deletetags':
    $tag = Horde_Util::getFormData('tag');
    if (!empty($image_id)) {
        $resource = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getImage($image_id);
        $page = Horde_Util::getFormData('page', 0);
        $url = Ansel::getUrlFor(
            'view',
            array_merge(
                array('view' => 'Image',
                      'gallery' => $gallery_id,
                      'image' => $image_id,
                      'page' => $page),
                $date),
            true);
    } else {
        $resource = $gallery;
        $url = Ansel::getUrlFor(
            'view',
            array_merge(
                array('view' => 'Gallery',
                      'gallery' => $gallery_id),
                $date),
            true);
    }
    $resource->removeTag($tag);
    $url->redirect();
    exit;

case 'modify':
    try {
        $image = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getImage($image_id);
        $ret = Horde_Util::getFormData('ret', 'gallery');
    } catch (Ansel_Exception $e) {
        $notification->push(_("Photo not found."), 'horde.error');
        Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
        exit;
    }
    $title = sprintf(_("Edit properties :: %s"), $image->filename);

    // Set up the form object.
    $vars = Horde_Variables::getDefaultVariables();
    if ($ret == 'gallery') {
        $vars->set('actionID', 'saveclose');
    } else {
        $vars->set('actionID', 'savecloseimage');
    }
    $form = new Ansel_Form_Image($vars, $title);
    $renderer = new Horde_Form_Renderer();

    // Set up the gallery attributes.
    $vars->set('image_default', $image->id == $gallery->get('default'));
    $vars->set('image_desc', $image->caption);
    $vars->set('image_tags', implode(', ', $image->getTags()));
    $vars->set('image_originalDate', $image->originalDate);
    $vars->set('image_uploaded', $image->uploaded);

    $page_output->header(array(
        'title' => $title
    ));
    $form->renderActive($renderer, $vars, Horde::url('image.php'), 'post', 'multipart/form-data');
    $page_output->footer();
    exit;

case 'savecloseimage':
case 'saveclose':
case 'save':
    $title = _("Save Photo");
    if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(
            sprintf(_("Access denied saving photo to \"%s\"."), $gallery->get('name')),
            'horde.error');
        Ansel::getUrlFor(
            'view',
            array_merge(
                array(
                    'gallery' => $gallery_id,
                    'slug' => $gallery->get('slug'),
                    'view' => 'Gallery',
                    'page' => $page
                ),
                $date
            ),
            true)->redirect();
        exit;
    }

    // Validate the form object.
    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('actionID', 'save');
    $renderer = new Horde_Form_Renderer();
    $form = new Ansel_Form_Image($vars, _("Edit a photo"));

    // Update existing image.
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        // See if we were replacing photo
        if (!empty($info['file0']['file'])) {
            try {
                $browser->wasFileUploaded('file0');
                if (filesize($info['file0']['file'])) {
                    $data = file_get_contents($info['file0']['file']);
                    if (getimagesize($info['file0']['file']) === false) {
                        $notification->push(_("The file you uploaded does not appear to be a valid photo."), 'horde.error');
                        unset($data);
                    }
                }
            } catch (Horde_Browser_Exception $e) {}
        }

        $image = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getImage($image_id);
        $image->caption = $vars->get('image_desc');
        $image->setTags(explode(',' , $vars->get('image_tags')));

        $newDate = new Horde_Date($vars->get('image_originalDate'));
        $image->originalDate = (int)$newDate->timestamp();

        if (!empty($data)) {
            try {
                $image->replace($data);
            } catch (Ansel_Exception $e) {
                $notification->push(
                    _("There was an error replacing the photo."), 'horde.error');
            }
        }
        $image->save();

        if ($vars->get('image_default')) {
            if ($gallery->get('default') != $image_id) {
                // Changing default - force refresh of stack
                // If we have a default-pretty already, make sure we delete it
                $ids = unserialize($gallery->get('default_prettythumb'));
                if (is_array($ids)) {
                    foreach ($ids as $imageId) {
                        $gallery->removeImage($imageId, true);
                    }
                }
                $gallery->set('default_prettythumb', '');
            }
            $gallery->set('default', $image_id);
            $gallery->set('default_type', 'manual');
        } elseif ($gallery->get('default') == $image_id) {
            // Currently set as default, but we no longer wish it.
            $gallery->set('default', 0);
            $gallery->set('default_type', 'auto');
            // If we have a default-pretty already, make sure we delete it
            $ids = unserialize($gallery->get('default_prettythumb'));
            if (is_array($ids)) {
                foreach ($ids as $imageId) {
                    $gallery->removeImage($imageId);
                }
            }
            $gallery->set('default_prettythumb', '');
        }

        $gallery->save();
        $imageurl = Ansel::getUrlFor(
            'view',
            array_merge(
                array(
                    'gallery' => $gallery_id,
                    'image' => $image_id,
                    'view' => 'Image',
                    'page' => $page),
                $date),
            true
        );
        if ($actionID == 'save') {
            $imageurl->redirect();
        } elseif ($actionID == 'saveclose') {
            echo Horde::wrapInlineScript(array(
                'window.opener.location.href = window.opener.location.href;',
                'window.close();'
            ));
        } else {
            echo Horde::wrapInlineScript(array(
                'window.opener.location.href = "' . $imageurl . '";',
                'window.close();'
            ));
        }
        exit;
    }
    break;

case 'editimage':
case 'cropedit':
case 'resizeedit':
    $imageGenerator_url = Ansel::getUrlFor(
        'view',
        array_merge(
            array(
                'gallery' => $gallery_id,
                'image' => $image_id,
                'view' => 'Image',
                'page' => $page),
            $date),
        true
    );
    $imageurl = Horde::url('image.php')->add(
        array_merge(
            array(
                'gallery' => $gallery_id,
                'slug' => $gallery->get('slug'),
                'image' => $image_id,
                'page' => $page),
        $date)
    );

    $galleryurl = Ansel::getUrlFor(
        'view',
        array_merge(
            array(
                'gallery' => $gallery_id,
                'page' => $page,
                'view' => 'Gallery',
                'slug' => $gallery->get('slug')),
            $date
        )
    );

    if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(
            _("Access denied editing the photo."),
            'horde.error');

        // Return to the image view.
        $imageGenerator_url->redirect();
        exit;
    }

    // Retrieve image details.
    $image = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->getImage($image_id);
    $title = sprintf(
        _("Edit %s :: %s"), $gallery->get('name'), $image->filename);

    if ($actionID == 'cropedit') {
        $geometry = $image->getDimensions('full');
        $x1 = 0;
        $y1 = 0;
        $x2 = $geometry['width'];
        $y2 = $geometry['height'];

        // js and css files
        $page_output->addScriptFile('scriptaculous/builder.js', 'horde');
        $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
        $page_output->addScriptFile('scriptaculous/controls.js', 'horde');
        $page_output->addScriptFile('scriptaculous/dragdrop.js', 'horde');
        $page_output->addScriptFile('cropper.js');

        $page_output->addThemeStylesheet('cropper.css');
    } elseif ($actionID == 'resizeedit') {
        // js and css files
        $geometry = $image->getDimensions('full');
        $page_output->addScriptFile('scriptaculous/builder.js', 'horde');
        $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
        $page_output->addScriptFile('scriptaculous/controls.js', 'horde');
        $page_output->addScriptFile('scriptaculous/dragdrop.js', 'horde');
    }

    $page_output->header(array(
        'title' => $title
    ));
    $notification->notify(array('listeners' => 'status'));

    if ($actionID == 'cropedit') {
        require ANSEL_TEMPLATES . '/image/crop_image.inc';
    } elseif ($actionID == 'resizeedit') {
        require ANSEL_TEMPLATES . '/image/resize_image.inc';
    } else {
        require ANSEL_TEMPLATES . '/image/edit_image.inc';
    }
    $page_output->footer();
    exit;

case 'watermark':
    if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(
            sprintf(_("Access denied saving photo to \"%s\"."), $gallery->get('name')),
            'horde.error');
        // Return to the image view
        Ansel::getUrlFor(
            'view',
            array_merge(
                array(
                    'gallery' => $gallery_id,
                    'image' => $image_id,
                    'view' => 'Image',
                    'page' => $page,
                    'slug' => $gallery->get('slug')),
                $date),
            true)->redirect();
        exit;
    } else {
        $image = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getImage($image_id);
        $image->watermark(
            'screen', $watermark, $watermark_halign,
            $watermark_valign, $watermark_font);
        $image->updateData($image->raw('screen'), 'screen');
        Horde::url('image.php', true)->add(
            array_merge(
                array(
                    'gallery' => $gallery_id,
                    'image' => $image_id,
                    'actionID' => 'editimage',
                    'page' => $page),
                $date))->redirect();
        exit;
    }

case 'rotate90':
case 'rotate180':
case 'rotate270':
case 'flip':
case 'mirror':
case 'grayscale':
case 'crop':
case 'resize':
    if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(
            sprintf(_("Access denied saving photo to \"%s\"."), $gallery->get('name')),
            'horde.error');
    } else {
        try {
            $image = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getImage($image_id);
        } catch (Ansel_Exception $e) {
            $notification->push($e->getMessage(), 'horde.error');
            Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
            exit;
        }

        switch ($actionID) {
        case 'rotate90':
        case 'rotate180':
        case 'rotate270':
            $angle = intval(substr($actionID, 6));
            try {
                $image->rotate('full', $angle);
            } catch (Ansel_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                $notification->push($e->getMessage(), 'horde.error');
                $error = true;
            }
            break;

        case 'flip':
            try {
                $image->flip('full');
            } catch (Ansel_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                $notification->push($e->getMessage(), 'horde.error');
                $error = true;
            }
            break;

        case 'mirror':
            try {
                $image->mirror('full');
            } catch (Ansel_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                $notification->push($e->getMessage(), 'horde.error');
                $error = true;
            }
            break;

        case 'grayscale':
            try {
                $image->grayscale('full');
            } catch (Ansel_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                $notification->push($e->getMessage(), 'horde.error');
                $error = true;
            }
            break;

        case 'crop':
            $image->load('full');
            $params = Horde_Util::getFormData('params');
            list($x1, $y1, $x2, $y2) = explode('.', $params);
            try {
                $image->crop($x1, $y1, $x2, $y2);
            } catch (Ansel_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                $notification->push($e->getMessage(), 'horde.error');
                $error = true;
            }
            break;
        case 'resize':
            $image->load('full');
            $width = Horde_Util::getFormData('width');
            $height = Horde_Util::getFormData('height');
            try {
                $image->resize($width, $height, true);
            } catch (Ansel_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                $notification->push($e->getMessage(), 'horde.error');
                $error = true;
            }
            break;
        }
        if (empty($error)) {
            $image->updateData($image->raw());
        }
    }

    Horde::url('image.php', true)->add(
        array_merge(
            array(
                'gallery' => $gallery_id,
                'image' => $image_id,
                'actionID' => 'editimage',
                'page' => $page),
            $date))->redirect();
    exit;

case 'setwatermark':
    $title = _("Watermark");
    try {
        $image = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getImage($image_id);
    } catch (Ansel_Exception $e) {
        $notification->push($image->getMessage(), 'horde.error');
        Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
        exit;
    }
    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('actionID', 'previewcustomwatermark');
    $form = new Ansel_Form_Watermark($vars, _("Watermark"));
    $renderer = new Horde_Form_Renderer();

    $page_output->header(array(
        'title' => $title
    ));
    $form->renderActive($renderer, $vars, Horde::url('image.php'), 'post');
    $page_output->footer();
    exit;

case 'previewcustomwatermark':
    $imageurl = Horde::url('image.php', true)->add(
        array_merge(
            array(
                'gallery' => $gallery_id,
                'image' => $image_id,
                'page' => $page,
                'watermark' => $watermark,
                'font' => $watermark_font,
                'whalign' => $watermark_halign,
                'wvalign' => $watermark_valign,
                'actionID' => 'previewwatermark'),
            $date));

    echo Horde::wrapInlineScript(array(
        'window.opener.location.href = "' . $imageurl . '";',
        'window.close();'
    ));
    exit;

case 'previewgrayscale':
case 'previewwatermark':
case 'previewflip':
case 'previewmirror':
case 'previewrotate90':
case 'previewrotate180':
case 'previewrotate270':
    $title = _("Edit Photo");
    $action = substr($actionID, 7);
    $image = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->getImage($image_id);
    $title = sprintf(
        _("Preview changes for %s :: %s"), $gallery->get('name'), $image->filename);

    $page_output->header(array(
        'title' => $title
    ));
    require ANSEL_TEMPLATES . '/image/preview_image.inc';
    $page_output->footer();
    exit;

case 'imagerotate90':
case 'imagerotate180':
case 'imagerotate270':
    $view = Horde_Util::getFormData('view');
    $angle = intval(substr($actionID, 11));
    $image = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->getImage($image_id);
    $image->rotate($view, $angle);
    $image->display($view);
    exit;

case 'imageflip':
    $view = Horde_Util::getFormData('view');
    $image = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->getImage($image_id);
    $image->flip($view);
    $image->display($view);
    exit;

case 'imagemirror':
    $view = Horde_Util::getFormData('view');
    $image = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->getImage($image_id);
    $image->mirror($view);
    $image->display($view);
    exit;

case 'imagegrayscale':
    $view = Horde_Util::getFormData('view');
    $image = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->getImage($image_id);
    $image->grayscale($view);
    $image->display($view);
    exit;

case 'imagewatermark':
    $view = Horde_Util::getFormData('view');
    $image = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->getImage($image_id);
    $image->watermark(
        $view, $watermark, $watermark_halign, $watermark_valign, $watermark_font);
    $image->display($view);
    exit;

case 'delete':
    if (is_array($image_id)) {
        $images = array_keys($image_id);
    } else {
        $images = array($image_id);
    }
    if (count($images)) {
        if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::DELETE)) {
            $notification->push(
                sprintf(_("Access denied deleting photos from \"%s\"."), $gallery->get('name')), 'horde.error');
        } else {
            foreach ($images as $image) {
                try {
                    $gallery->removeImage($image);
                    $notification->push(_("Deleted the photo."), 'horde.success');
                } catch (Ansel_Exception $e) {
                    $notification->push(
                        sprintf(_("There was a problem deleting photos: %s"), $e->getMessage()), 'horde.error');
                }
            }
        }
    }

    // Recalculate the number of pages, since it might have changed
    $children = $gallery->countGalleryChildren(Horde_Perms::SHOW);
    $perpage = min(
        $prefs->getValue('tilesperpage'),
        $conf['thumbnail']['perpage']);
    $pages = ceil($children / $perpage);
    if ($page > $pages) {
        $page = $pages;
    }

    // Return to the image list.
    Ansel::getUrlFor(
        'view',
        array_merge(
            array(
                'gallery' => $gallery_id,
                'view' => 'Gallery',
                'page' => $page,
                'slug' => $gallery->get('slug')),
            $date),
        true)->redirect();
    exit;

case 'move':
    if (is_array($image_id)) {
        $images = array_keys($image_id);
    } else {
        $images = array($image_id);
    }
    $newGallery = Horde_Util::getFormData('new_gallery');
    if ($images && $newGallery) {
        try {
            $newGallery = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getGallery($newGallery);
            $result = $gallery->moveImagesTo($images, $newGallery);
            $notification->push(
                sprintf(ngettext(
                    "Moved %d photo from \"%s\" to \"%s\"",
                    "Moved %d photos from \"%s\" to \"%s\"",
                    $result),
                $result, $gallery->get('name'), $newGallery->get('name')),
            'horde.success');
        } catch (Ansel_Exception $e) {
            $notification->push(_("Bad input."), 'horde.error');
        }
    }

    // Recalculate the number of pages, since it might have changed
    $children = $gallery->countGalleryChildren(Horde_Perms::SHOW);
    $perpage = min(
        $prefs->getValue('tilesperpage'),
        $conf['thumbnail']['perpage']);
    $pages = ceil($children / $perpage);
    if ($page > $pages) {
        $page = $pages;
    }

    // Return to the image list.
    Ansel::getUrlFor(
        'view',
        array_merge(
            array(
                'gallery' => $gallery_id,
                'view' => 'Gallery',
                'page' => $page,
                'slug' => $gallery->get('slug')),
            $date),
        true)->redirect();
    exit;

case 'copy':
    if (is_array($image_id)) {
        $images = array_keys($image_id);
    } else {
        $images = array($image_id);
    }
    $newGallery = Horde_Util::getFormData('new_gallery');
    if ($images && $newGallery) {
        try {
            $newGallery = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getGallery($newGallery);
            $result = $gallery->copyImagesTo($images, $newGallery);
            $notification->push(
                sprintf(
                    ngettext(
                        "Copied %d photo to %s",
                        "Copied %d photos to %s",
                        $result),
                    $result, $newGallery->get('name')),
                'horde.success');
        } catch (Ansel_Exception $e) {
            $notification->push(_("Bad input."), 'horde.error');

       }
    }

    //Return to the image list.
    Ansel::getUrlFor(
        'view',
        array_merge(
            array(
                'gallery' => $gallery_id,
                'view' => 'Gallery',
                'page' => $page,
                'slug' => $gallery->get('slug')),
            $date),
        true)->redirect();
    exit;

case 'downloadzip':
    $galleryId = Horde_Util::getFormData('gallery');
    if ($galleryId) {
        $gallery = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getGallery($galleryId);
        if (!$registry->getAuth() ||
            !$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ) ||
            $gallery->hasPasswd() || !$gallery->isOldEnough()) {

            $notification->push(
                sprintf(_("Access denied downloading photos from \"%s\"."), $gallery->get('name')), 'horde.error');
            Horde::url('view.php?view=List', true)->redirect();
            exit;
        }
    }
    if (count($image_id)) {
        Ansel::downloadImagesAsZip(null, array_keys($image_id));
    } else {
        $notification->push(_("You must select images to download."), 'horde.error');
        if ($galleryId) {
            $url = Ansel::getUrlFor(
                'view',
                array(
                    'gallery' => $galleryId,
                    'view' => 'Gallery',
                    'page' => $page,
                    'slug' => $gallery->get('slug')));
        } else {
            $url = Ansel::getUrlFor('view', array('view' => 'List'));
        }
        $url->redirect();
        exit;
    }
    exit;

case 'previewcrop':
    if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(_("Access denied editing the photo."), 'horde.error');
        Ansel::getUrlFor(
            'view',
            array(
                'gallery' => $gallery_id,
                'image' => $image_id,
                'view' => 'Image',
                'page' => $page))->redirect();
    } else {
        $x1 = (int)Horde_Util::getFormData('x1');
        $y1 = (int)Horde_Util::getFormData('y1');
        $x2 = (int)Horde_Util::getFormData('x2');
        $y2 = (int)Horde_Util::getFormData('y2');
        $title = _("Crop");
        $action = substr($actionID, 7);

        $image = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getImage($image_id);
        $title = sprintf(
            _("Preview changes for %s :: %s"),
            $gallery->get('name'),
            $image->filename);
        $params = $x1 . '.' . $y1 . '.' . $x2 . '.' . $y2;

        $page_output->header(array(
            'title' => $title
        ));
        require ANSEL_TEMPLATES . '/image/preview_cropimage.inc';
        $page_output->footer();
    }
    exit;

case 'imagecrop':
        if ($gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
            $params = Horde_Util::getFormData('params');
            list($x1, $y1, $x2, $y2) = explode('.', $params);
            $image = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getImage($image_id);
            $image->load('full');
            $image->crop($x1, $y1, $x2, $y2);
            $image->display();
        }
        exit;

default:
    Ansel::getUrlFor('default_view', array())->redirect();
    exit;
}

$page_output->header(array(
    'title' => $title
));
$form->renderActive($renderer, $vars, Horde::url('image.php'), 'post',
                    'multipart/form-data');
$page_output->footer();
