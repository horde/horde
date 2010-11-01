<?php
/**
 * Process an single image (to be called by ajax)
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once 'tabs.php';

/* Search from */
$form = new Horde_Form($vars);
$msg = _("Please upload photo with the face to search for. You can search only one face at a time.");
$form->addVariable(_("Face to search for"), 'image', 'file', true, false, $msg, array(false));
$form->setButtons(_("Upload"));

if ($form->validate()) {

    $form->getInfo(null, $info);

    $tmp = Horde::getTempDir();
    $img = Ansel::getImageObject();
    try {
        $img->loadFile($info['image']['file']);
        $dimensions = $img->getDimensions();
    } catch (Horde_Image_Exception $e) {
        $notification->push($e->getMessage());
        Horde::url('faces/search/image.php')->redirect();
        exit;
    }

    if ($dimensions['width'] < 50 || $dimensions['height'] < 50) {
        $notification->push(_("Photo is too small. Search photo must be at least 50x50 pixels."));
        Horde::url('faces/search/image.php')->redirect();
        exit;
    }

    try {
        $img->resize(min($conf['screen']['width'], $dimensions['width']),
                     min($conf['screen']['height'], $dimensions['height']));
    } catch (Horde_Image_Exception $e) {
        $notification->push($e->getMessage());
        Horde::url('faces/search/image.php')->redirect();
        exit;
    }

    $path = $tmp . '/search_face_' . $registry->getAuth() . Ansel_Faces::getExtension();
    if (file_put_contents($path, $img->raw())) {
        Horde::url('faces/search/image_define.php')->redirect();
    } else {
        $notification->push(_("Cannot store search photo"));
        Horde::url('faces/search/image.php')->redirect();
    }
    exit;

}

$title = _("Upload face photo");
require ANSEL_TEMPLATES . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
echo $tabs->render(Horde_Util::getGet('search_faces', 'image'));
$form->renderActive(null, null, null, 'post');

if (empty($name)) {
    // Do noting
} elseif (empty($results)) {
    echo _("No faces found");
} else {
    foreach ($results as $face_id => $face) {
        include ANSEL_TEMPLATES . '/tile/face.inc';
    }
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
