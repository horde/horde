<?php
/**
 * Script for saving a comic to an image gallery. UI template stolen from
 * Imp's saveimage functionality.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 * @author Michael Rubinsky <mrubinsk@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('klutz');

$index = Horde_Util::getFormData('index');
$date = Horde_Util::getFormData('date');
$actionID = Horde_Util::getFormData('actionID');
$tags = Horde_Util::getFormData('tags');

/* We'll need the comic object to build the image name */
$comic = $klutz->comicObject($index);

switch ($actionID) {
case 'save_comic':
    /* Try to retrieve the image directly from storage first*/
    $image = $klutz_driver->retrieveImage($index, $date);
    if (is_string($image) && substr($image, 0, 4) == 'http') {
        $comic = $klutz->comicObject($index);
        $image = $comic->fetchImage($date);
    }
    if (!is_a($image, 'Klutz_Image')) {
        PEAR::raiseError(_("There was an error retrieving the comic."));
    } else {
        $desc = Horde_Util::getFormData('desc', '');
        $image_data = array(
            'filename' => $comic->name . '-' . strftime('%m%d%Y', $date) . '.' . str_replace('image/', '', $image->type), // Suggestions for better name?
            'description' => $desc,
            'data' => $image->data,
            'type' => $image->type,
            'tags' => explode(',', $tags)
        );
        $gallery = Horde_Util::getFormData('gallery');
        $res = $registry->call('images/saveImage',
                               array(null, $gallery, $image_data));
        if (is_a($res, 'PEAR_Error')) {
            $notification->push($res, 'horde.error');
            break;
        }
        Horde_Util::closeWindowJS();
    }
    exit;
}

/* Build the list of galleries. */
$id = $prefs->getValue('comicgallery');
$gallerylist = $registry->call('images/selectGalleries',
                                    array(null, PERMS_EDIT, null,
                                            true, 0, 0, $id));

$page_output->header(array(
    'title' => _("Save Image")
));
require KLUTZ_TEMPLATES . '/savecomic.html.php';
$page_output->footer();
