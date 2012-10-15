<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

$galleryId = Horde_Util::getFormData('gallery');
if (!$galleryId) {
    Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
    exit;
}
try {
    $gallery = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->getGallery($galleryId);
} catch (Ansel_Exception $e) {
    $notification->push(
        sprintf(_("Error accessing %s: %s"), $galleryId, $e->getMessage()),
        'horde.error');
    Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
    exit;
}

if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
    $notification->push(
        sprintf(_("Access denied setting captions for %s."), $gallery->get('name')),
        'horde.error');
    Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
    exit;
}

// We might be browsing by date
$date = Ansel::getDateParameter();
$gallery->setDate($date);

// Run through the action handlers.
$do = Horde_Util::getFormData('do');
switch ($do) {
case 'save':
    // Save a batch of captions.
    $images = $gallery->getImages();
    foreach ($images as $image) {
        if (($caption = Horde_Util::getFormData('img' . $image->id)) !== null) {
            $image->caption = $caption;
            $image->save();
        }
    }

    $notification->push(_("Captions Saved."), 'horde.success');
    Ansel::getUrlFor(
        'view',
        array_merge(
            array(
                'gallery' => $galleryId,
                'slug' => $gallery->get('slug'),
                'view' => 'Gallery'),
            $date),
        true)->redirect();
    exit;
}

$page_output->header(array(
    'title' => _("Caption Editor")
));
$notification->notify(array('listeners' => 'status'));
require ANSEL_TEMPLATES . '/captions/captions.inc';
$page_output->footer();
