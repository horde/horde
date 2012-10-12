<?php
/**
 * Process an single image (to be called by ajax)
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 */
require_once 'tabs.php';

/* check if image exists */
$tmp = Horde::getTempDir();
$path = $tmp . '/search_face_' . $registry->getAuth() . Ansel_Faces::getExtension();

if (file_exists($path) !== true) {
    $notification->push(_("You must upload the search photo first"));
    Horde::url('faces/search/image.php')->redirect();
}

$title = _("Create a new face");

$x1 = 0;
$y1 = 0;
$x2 = 0;
$y2 = 0;

try {
    $faces = $faces->getFaces($path);
} catch (Ansel_Exception $e) {
    exit;
}

if (count($faces) > 1) {
    $notification->push(_("More then one face found in photo. Please note that you can search only one face at a time."));
} elseif (empty($faces)) {
    $notification->push(_("No faces found. Define you own."));
} else {
    $x1 = $faces[0]['x'];
    $y1 = $faces[0]['y'];
    $x2 = $faces[0]['x'] + $faces[0]['width'];
    $y2 = $faces[0]['y'] + $faces[0]['height'];
}

$height = $x2 - $x1;
$width = $y2 - $y1;

$page_output->addScriptFile('scriptaculous/scriptaculous.js', 'horde');
$page_output->addScriptFile('scriptaculous/builder.js', 'horde');
$page_output->addScriptFile('cropper.js');
$page_output->addScriptFile('stripe.js', 'horde');

$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
require ANSEL_TEMPLATES . '/faces/define.inc';
$page_output->footer();
