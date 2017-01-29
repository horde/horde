<?php
/**
 * Copyright 2003-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
/**
 * The Ansel_ActionHandler:: class centralizes the handling of various image
 * and gallery actions.
 *
 * Copyright 2003-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_ActionHandler
{
    /**
     * Check for download action.
     *
     * @param string $actionID  The action identifier.
     */
    public static function download($actionID)
    {
        global $notification, $registry, $storage;

        if ($actionID == 'downloadzip') {
            $gallery_id = Horde_Util::getFormData('gallery');
            $image_id = Horde_Util::getFormData('image');
            $image_id = !is_array($image_id)
                ? array($image_id)
                : array_keys($image_id);

            // All from same gallery.
            if ($gallery_id) {
                $gallery = $storage->getGallery($gallery_id);
                if (!$registry->getAuth() ||
                    !$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ) ||
                    $gallery->hasPasswd() || !$gallery->isOldEnough()) {

                    $notification->push(
                        _("Access denied downloading photos from this gallery."),
                        'horde.error');
                    return true;
                }
                $image_ids = $gallery->listImages();
            } else {
                $image_ids = array();
                foreach ($image_id as $image) {
                    $img = $storage->getImage($image);
                    $galleries[$img->gallery][] = $image;
                }
                foreach ($galleries as $gid => $images) {
                    $gallery = $storage->getGallery($gid);
                    if (!$registry->getAuth() || !$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ) |
                        $gallery->hasPasswd() || !$gallery->isOldEnough()) {

                        continue;
                    }
                    $image_ids = array_merge($image_ids, $images);
                }
            }
            if (count($image_ids)) {
                Ansel::downloadImagesAsZip(null, $image_ids);
            } else {
                $notification->push(_("You must select images to download."), 'horde.error');
            }
            return true;
        }

        return false;
    }

    /**
     * Check for, and handle, common image related actions.
     *
     * @param string $actionID  The action identifier.
     *
     * @return boolean  True if an action was handled, otherwise false.
     * @throws Ansel_Exception
     */
    public static function imageActions($actionID)
    {
        global $notification, $registry, $storage;

        if (self::download($actionID)) {
            return true;
        }

        switch($actionID) {
        case 'delete':
            $gallery_id = Horde_Util::getFormData('gallery');
            $image_id = Horde_Util::getFormData('image');
            $images = !is_array($image_id)
                ? array($image_id)
                : array_keys($image_id);

            foreach ($images as $image) {
                $img = $storage->getImage($image);
                if (empty($gallery_id)) {
                    $gallery_id = $img->gallery;
                }
                $gallery = $storage->getgallery($gallery_id);
                if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::DELETE)) {
                    $notification->push(_("Access denied deleting photos from this gallery."), 'horde.error');
                } else {
                    try {
                        $gallery->removeImage($image);
                        $notification->push(_("Deleted the photo."), 'horde.success');
                    } catch (Ansel_Exception $e) {
                        $notification->push(
                            sprintf(_("There was a problem deleting photos: %s"), $e->getMessage()), 'horde.error');
                    }
                }
            }
            return true;

        case 'move':
            $newGallery = Horde_Util::getFormData('new_gallery');
            $image_id = Horde_Util::getFormData('image');
            $images = !is_array($image_id)
                ? array($image_id)
                : array_keys($image_id);

            if ($images && $newGallery) {
                try {
                    $newGallery = $storage->getGallery($newGallery);
                    // Group by gallery first, then process in bulk by gallery.
                    $galleries = array();
                    foreach ($images as $image) {
                        $img = $storage->getImage($image);
                        $galleries[$img->gallery][] = $image;
                    }
                    foreach ($galleries as $gallery_id => $images) {
                        $gallery = $storage->getGallery($gallery_id);
                        try {
                            $result = $gallery->moveImagesTo($images, $newGallery);
                            $notification->push(
                                sprintf(ngettext("Moved %d photo from \"%s\" to \"%s\"",
                                                 "Moved %d photos from \"%s\" to \"%s\"",
                                                 count($images)),
                                        count($images), $gallery->get('name'),
                                        $newGallery->get('name')),
                                'horde.success');
                        } catch (Exception $e) {
                            $notification->push($e->getMessage(), 'horde.error');
                        }
                    }
                } catch (Ansel_Exception $e) {
                    $notification->push(_("Bad input."), 'horde.error');
                }
            }
            return true;

        case 'copy':
            $newGallery = Horde_Util::getFormData('new_gallery');
            $image_id = Horde_Util::getFormData('image');
            $images = !is_array($image_id)
                ? array($image_id)
                : array_keys($image_id);

            if ($images && $newGallery) {
                try {
                    // Group by gallery first, then process in bulk by gallery.
                    $newGallery = $storage->getGallery($newGallery);
                    $galleries = array();
                    foreach ($images as $image) {
                        $img = $storage->getImage($image);
                        $galleries[$img->gallery][] = $image;
                    }
                    foreach ($galleries as $gallery_id => $images) {
                        $gallery = $storage->getGallery($gallery_id);
                        try {
                            $result = $gallery->copyImagesTo($images, $newGallery);
                            $notification->push(sprintf(
                                ngettext("Copied %d photo from %s to %s",
                                         "Copied %d photos from %s to %s",
                                         count($images)),
                                count($images), $gallery->get('name'),
                                $newGallery->get('name')),
                                'horde.success');
                        } catch (Exception $e) {
                            $notification->push($e->getMessage(), 'horde.error');
                        }
                    }
                } catch (Ansel_Exception $e) {
                    $notification->push(_("Bad input."), 'horde.error');
                }
            }
            return true;

        case 'downloadzip':
            $gallery_id = Horde_Util::getFormData('gallery');
            $image_id = Horde_Util::getFormData('image');
            $images = !is_array($image_id)
                ? array($image_id)
                : array_keys($image_id);

            // All from same gallery.
            if ($gallery_id) {
                $gallery = $storage->getGallery($gallery_id);
                if (!$registry->getAuth() ||
                    !$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ) ||
                    $gallery->hasPasswd() || !$gallery->isOldEnough()) {

                    $notification->push(
                        _("Access denied downloading photos from this gallery."),
                        'horde.error');
                    return true;
                }
                $image_ids = $images;
            } else {
                $image_ids = array();
                foreach ($images as $image) {
                    $img = $storage->getImage($image);
                    $galleries[$img->gallery][] = $image;
                }
                foreach ($galleries as $gid => $images) {
                    $gallery = $storage->getGallery($gid);
                    if (!$registry->getAuth() || !$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ) |
                        $gallery->hasPasswd() || !$gallery->isOldEnough()) {

                        continue;
                    }
                    $image_ids = array_merge($image_ids, $images);
                }
            }
            if (count($image_ids)) {
                Ansel::downloadImagesAsZip(null, $image_ids);
            } else {
                $notification->push(_("You must select images to download."), 'horde.error');
            }
            return true;
        }

        return false;
    }

    /**
     * Check for, and handle, image editing actions.
     *
     * @param string $actionID  The action identifier.
     *
     * @return boolean  True if an action was handled, otherwise false.
     * @throws Ansel_Exception
     */
    public static function editActions($actionID)
    {
        global $notification, $page_output, $registry, $storage, $injector;

        $gallery_id = Horde_Util::getFormData('gallery');
        $image_id = Horde_Util::getFormData('image');
        $date = Ansel::getDateParameter();
        $page = Horde_Util::getFormData('page', 0);
        $watermark_font = Horde_Util::getFormData('font');
        $watermark_halign = Horde_Util::getFormData('whalign');
        $watermark_valign = Horde_Util::getFormData('wvalign');
        $watermark = Horde_Util::getFormData('watermark', $GLOBALS['prefs']->getValue('watermark_text'));

        // Get the gallery object and style information.
        try {
            $gallery = $storage->getGallery($gallery_id);
        } catch (Ansel_Exception $e) {
            $notification->push(
                sprintf(_("Gallery %s not found."), $gallery_id),
                'horde.error');
            Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
            exit;
        }
        switch ($actionID) {
        case 'modify':
            try {
                $image = $storage->getImage($image_id);
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
            $vars->set('image_title', $image->title);
            $vars->set('image_tags', implode(', ', $image->getTags()));
            $vars->set('image_originalDate', $image->originalDate);
            $vars->set('image_uploaded', $image->uploaded);

            $page_output->header(array(
                'title' => $title
            ));
            $form->renderActive(
                $renderer,
                $vars,
                Horde::url('image.php'), 'post', 'multipart/form-data');
            $page_output->footer();
            exit;

        case 'savecloseimage':
        case 'saveclose':
        case 'save':
            $title = _("Save Photo");
            if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
                $notification->push(
                    _("Access denied saving photo to this gallery."),
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
                // Replacing photo
                if (!empty($info['file0']['file'])) {
                    try {
                        $GLOBALS['browser']->wasFileUploaded('file0');
                        if (filesize($info['file0']['file'])) {
                            $data = file_get_contents($info['file0']['file']);
                            if (getimagesize($info['file0']['file']) === false) {
                                $notification->push(_("The file you uploaded does not appear to be a valid photo."), 'horde.error');
                                unset($data);
                            }
                        }
                    } catch (Horde_Browser_Exception $e) {}
                }

                $image = $storage->getImage($image_id);
                $image->caption = $vars->get('image_desc');
                $image->title = $vars->get('image_title');
                $image->setTags(explode(',' , $vars->get('image_tags')));
                $newDate = new Horde_Date($vars->get('image_originalDate'));
                $image->originalDate = (int)$newDate->timestamp();
                if (!empty($data)) {
                    try {
                        $image->replace($data);
                    } catch (Ansel_Exception $e) {
                        $notification->push(
                            _("There was an error replacing the photo."),
                            'horde.error');
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
                    $page_output->addInlineScript(array(
                        'window.opener.location.href = window.opener.location.href;',
                        'window.close();'
                    ));
                    $page_output->outputInlineScript();
                } else {
                    $page_output->addInlineScript(array(
                        'window.opener.location.href = "' . $imageurl . '";',
                        'window.close();'
                    ));
                    $page_output->outputInlineScript();
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
                    $date
                )
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
            $image = $storage->getImage($image_id);
            $title = sprintf(_("Edit %s :: %s"), $gallery->get('name'), $image->filename);

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
                $page_output->addInlineScript('imageCropper.init();', true);
                $page_output->addThemeStylesheet('cropper.css');
            } elseif ($actionID == 'resizeedit') {
                // js and css files
                $geometry = $image->getDimensions('full');
                $page_output->addScriptFile('scriptaculous/builder.js', 'horde');
                $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
                $page_output->addScriptFile('scriptaculous/controls.js', 'horde');
                $page_output->addScriptFile('scriptaculous/dragdrop.js', 'horde');
                $page_output->addScriptFile('scriptaculous/slider.js', 'horde');
                $page_output->addScriptFile('resizeimage.js');
                $js = array(
                    'window.Ansel = window.Ansel || {}',
                    'Ansel.image_geometry = ' . Horde_Serialize::serialize($geometry, Horde_Serialize::JSON),
                    "Ansel.slider = new Control.Slider(
                        'handle1',
                        'slider-track',
                        {
                            minimum: 1,
                            maximum: Ansel.image_geometry['width'],
                            sliderValue: Ansel.image_geometry['width'],
                            handleImage: 'ansel_slider_img',
                            axis: 'horizontal',
                            onChange: function(e) { resizeImage(e * Ansel.image_geometry['width']); },
                            onSlide: function(e) { resizeImage(e * Ansel.image_geometry['width']); }
                        }
                    );"

                );
                $page_output->addInlineScript($js, true);
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
            if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
                $notification->push(
                    _("Access denied saving photo to this gallery."),
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
                $image = $storage->getImage($image_id);
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
                    _("Access denied saving photo to this gallery."),
                    'horde.error');
            } else {
                try {
                    $image = $storage->getImage($image_id);
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
                        Horde::log($e->getMessage(), 'ERR');
                        $notification->push($e->getMessage(), 'horde.error');
                        $error = true;
                    }
                    break;

                case 'flip':
                    try {
                        $image->flip('full');
                    } catch (Ansel_Exception $e) {
                        Horde::log($e->getMessage(), 'ERR');
                        $notification->push($e->getMessage(), 'horde.error');
                        $error = true;
                    }
                    break;

                case 'mirror':
                    try {
                        $image->mirror('full');
                    } catch (Ansel_Exception $e) {
                        Horde::log($e->getMessage(), 'ERR');
                        $notification->push($e->getMessage(), 'horde.error');
                        $error = true;
                    }
                    break;

                case 'grayscale':
                    try {
                        $image->grayscale('full');
                    } catch (Ansel_Exception $e) {
                        Horde::log($e->getMessage(), 'ERR');
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
                        Horde::log($e->getMessage(), 'ERR');
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
                        Horde::log($e->getMessage(), 'ERR');
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
                $image = $storage->getImage($image_id);
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

            $page_output->addInlineScript(array(
                'window.opener.location.href = "' . $imageurl . '";',
                'window.close();'
            ));
            $page_output->outputInlineScript();
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
            $image = $storage->getImage($image_id);
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
            $image = $storage->getImage($image_id);
            $image->rotate($view, $angle);
            $image->display($view);
            exit;

        case 'imageflip':
            $view = Horde_Util::getFormData('view');
            $image = $storage->getImage($image_id);
            $image->flip($view);
            $image->display($view);
            exit;

        case 'imagemirror':
            $view = Horde_Util::getFormData('view');
            $image = $storage->getImage($image_id);
            $image->mirror($view);
            $image->display($view);
            exit;

        case 'imagegrayscale':
            $view = Horde_Util::getFormData('view');
            $image = $storage->getImage($image_id);
            $image->grayscale($view);
            $image->display($view);
            exit;

        case 'imagewatermark':
            $view = Horde_Util::getFormData('view');
            $image = $storage->getImage($image_id);
            $image->watermark(
                $view, $watermark, $watermark_halign, $watermark_valign, $watermark_font);
            $image->display($view);
            exit;

        case 'previewcrop':
            if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
                $notification->push(
                    _("Access denied editing the photo."),
                    'horde.error');
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
                $image = $storage->getImage($image_id);
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
                    $image = $storage->getImage($image_id);
                    $image->load('full');
                    $image->crop($x1, $y1, $x2, $y2);
                    $image->display();
                }
                exit;
        }

        return false;
    }

    public static function galleryActions($actionID)
    {
        global $registry, $notification, $page_output, $storage;

        if (self::download($actionID)) {
            return true;
        }

        switch ($actionID) {
        case 'add':
        case 'addchild':
        case 'save':
        case 'modify':
            $view = new Ansel_View_GalleryProperties(
                array(
                    'actionID' => $actionID,
                    'url' => new Horde_Url(Horde_Util::getFormData('url')),
                    'gallery' => Horde_Util::getFormData('gallery')));
            $view->run();
            exit;

        case 'downloadzip':
            $galleryId = Horde_Util::getFormData('gallery');
            try {
                $gallery = $storage->getGallery($galleryId);
                if (!$registry->getAuth() ||
                    !$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ)) {

                    $notification->push(_("Access denied downloading photos from this gallery."), 'horde.error');
                    Horde::url('view.php?view=List', true)->redirect();
                    exit;
                }
                Ansel::downloadImagesAsZip($gallery);
            } catch (Ansel_Exception $e) {
                $notification->push($gallery->getMessage(), 'horde.error');
                Horde::url('view.php?view=List', true)->redirect();
                exit;
            }
            exit;

        case 'delete':
        case 'empty':
            // Print the confirmation screen.
            $galleryId = Horde_Util::getFormData('gallery');
            if ($galleryId) {
                try {
                    $gallery = $storage->getGallery($galleryId);
                    $page_output->header();
                    $notification->notify(array('listeners' => 'status'));
                    require ANSEL_TEMPLATES . '/gallery/delete_confirmation.inc';
                    $page_output->footer();
                    exit;
                } catch (Ansel_Exception $e) {
                    $notification->push($gallery->getMessage(), 'horde.error');
                }
            }

            // Return to the gallery list.
            Horde::url(Ansel::getUrlFor('view', array('view' => 'List'), true))->redirect();
            exit;
        case 'do_delete':
        case 'do_empty':
            $galleryId = Horde_Util::getPost('gallery');
           try {
                $gallery = $storage->getGallery($galleryId);
            } catch (Ansel_Exception $e) {
                $notification->push($e->getMessage(), 'horde.error');
                Ansel::getUrlFor('default_view', array())->redirect();
                exit;
            }
            switch ($actionID) {
            case 'do_delete':
                if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::DELETE)) {
                    $notification->push(
                        _("Access denied deleting this gallery."),
                        'horde.error');
                } else {
                    try {
                        $storage->removeGallery($gallery);
                        $notification->push(sprintf(
                            _("Successfully deleted %s."),
                            $gallery->get('name')), 'horde.success');
                    } catch (Ansel_Exception $e) {
                        $notification->push(sprintf(
                            _("There was a problem deleting %s: %s"),
                            $gallery->get('name'), $e->getMessage()),
                            'horde.error');
                    } catch (Horde_Exception_NotFound $e) {
                        Horde::log($e, 'err');
                    }
                }

                // Return to the default view.
                Ansel::getUrlFor('default_view', array())->redirect();
                exit;

            case 'do_empty':
                if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::DELETE)) {
                    $notification->push(
                        _("Access denied deleting this gallery."),
                        'horde.error');
                } else {
                    $storage->emptyGallery($gallery);
                    $notification->push(sprintf(
                        _("Successfully emptied \"%s\""),
                        $gallery->get('name')),
                        'horde.success');
                }
                Ansel::getUrlFor(
                    'view',
                    array(
                        'view' => 'Gallery',
                        'gallery' => $galleryId,
                        'slug' => $gallery->get('slug')),
                    true)->redirect();
                exit;
            default:
                 Ansel::getUrlFor(
                    'view',
                    array(
                        'view' => 'Gallery',
                        'gallery' => $galleryId,
                        'slug' => $gallery->get('slug')),
                    true)->redirect();
                exit;
            }

        case 'generateDefault':
            // Re-generate the default pretty gallery image.
            $galleryId = Horde_Util::getFormData('gallery');
            try {
                $gallery = $storage->getGallery($galleryId);
                $gallery->clearStacks();
                $notification->push(_("The gallery's default photo has successfully been reset."), 'horde.success');
                Horde::url('view.php', true)->add('gallery', $galleryId)->redirect();
                exit;
            } catch (Ansel_Exception $e) {
                $notification->push($e->getMessage(), 'horde.error');
                Horde::url('index.php', true)->redirect();
                exit;
            }

        case 'generateThumbs':
            // Re-generate all of this gallery's prettythumbs.
            $galleryId = Horde_Util::getFormData('gallery');
            try {
                $gallery = $storage->getGallery($galleryId);
            } catch (Ansel_Exception $e) {
                $notification->push($gallery->getMessage(), 'horde.error');
                Horde::url('index.php', true)->redirect();
                exit;
            }
            $gallery->clearThumbs();
            $notification->push(_("The gallery's thumbnails have successfully been reset."), 'horde.success');
            Horde::url('view.php', true)->add('gallery', $galleryId)->redirect();
            exit;

        case 'deleteCache':
            // Delete all cached image views.
            $galleryId = Horde_Util::getFormData('gallery');
            try {
                $gallery = $storage->getGallery($galleryId);
            } catch (Ansel_Exception $e) {
                $notification->push($gallery->getMessage(), 'horde.error');
                Horde::url('index.php', true)->redirect();
                exit;
            }
            $gallery->clearViews();
            $notification->push(_("The gallery's views have successfully been reset."), 'horde.success');
            Horde::url('view.php', true)->add('gallery', $galleryId)->redirect();
            exit;
        }

        return false;
    }

}
