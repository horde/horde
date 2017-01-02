<?php
/**
 * Responsible for making changes to image properties as well as making,
 * previewing and saving changes to the image.
 *
 * Copyright 2003-2017 Horde LLC (http://www.horde.org/)
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
$page = Horde_Util::getFormData('page', 0);

// None of the views on this page display side or top bars.
$page_output->topbar = $page_output->sidebar = false;

// Check basic image actions.
if (Ansel_ActionHandler::imageActions($actionID)) {
    $gallery_id = Horde_Util::getFormData('gallery');
    $gallery = $injector->getInstance('Ansel_Storage')->getGallery($gallery_id);
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
}

// Edit actions?
if (!Ansel_ActionHandler::editActions($actionID)) {
    $page_output->header(array(
        'title' => $title
    ));
    $form->renderActive($renderer, $vars, Horde::url('image.php'), 'post', 'multipart/form-data');
    $page_output->footer();
}
