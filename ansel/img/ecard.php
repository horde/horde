<?php
/**
 * Copyright 2004-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

/* Abort if ecard sending is disabled. */
if (empty($conf['ecard']['enable'])) {
    exit;
}

$title = sprintf(_("Send Ecard :: %s"), $image->filename);

/* Get the gallery and the image, and abort if either fails. */
$gallery = $injector->getInstance('Ansel_Storage')
    ->getGallery(Horde_Util::getFormData('gallery'));
$image = $gallery->getImage(Horde_Util::getFormData('image'));

$vars = Horde_Variables::getDefaultVariables();
$form = new Ansel_Form_Ecard($vars, $title);
if ($form->validate($vars)) {
    Ansel::sendEcard($image);
    echo Horde::wrapInlineScript(array('window.close();'));
    exit;
}

/* Set up the form object. */
$renderer = new Horde_Form_Renderer();
$vars->set('actionID', 'send');
$vars->set('image_desc', strlen($image->caption) ? $image->caption : $image->filename);
$editor = $injector->getInstance('Horde_Editor');
if ($editor->supportedByBrowser()) {
    $editor->initialize(array('id' => 'ecard_comments'));
    $vars->set('rtemode', 1);
    $form->addHidden('', 'rtemode', 'text', false);
}

$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
$form->renderActive($renderer, $vars, Horde::url('img/ecard.php'), 'post', 'multipart/form-data');
$page_output->footer();
