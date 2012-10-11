<?php
/**
 * Save an image to a registry-defined application.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp');

if (!$registry->hasMethod('images/selectGalleries') ||
    !$registry->hasMethod('images/saveImage')) {
    $e = new IMP_Exception('Image saving is not available.');
    $e->logged = true;
    throw $e;
}

/* Run through the action handlers. */
$vars = $injector->getInstance('Horde_Variables');
switch ($vars->actionID) {
case 'save_image':
    $contents = $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices($vars->mbox, $vars->uid));
    $mime_part = $contents->getMIMEPart($vars->id);
    $image_data = array(
        'data' => $mime_part->getContents(),
        'description' => $mime_part->getDescription(true),
        'filename' => $mime_part->getName(true),
        'type' => $mime_part->getType()
    );
    try {
        $registry->images->saveImage($vars->gallery, $image_data);
    } catch (Horde_Exception $e) {
        $notification->push($e);
        break;
    }
    echo Horde::wrapInlineScript(array('window.close();'));
    exit;
}

/* Build the view. */
$view = new Horde_View(array(
    'templatePath' => IMP_TEMPLATES . '/saveimage'
));
$view->addHelper('Horde_Core_View_Helper_Image');
$view->addHelper('Text');

$view->action = Horde::url('saveimage.php');
$view->gallerylist = $registry->images->selectGalleries(array('perm' => Horde_Perms::EDIT));
$view->id = $vars->id;
$view->mbox = $vars->mbox;
$view->uid = $vars->uid;

$page_output->topbar = $page_output->sidebar = false;

$page_output->addInlineScript(array(
    '$$("INPUT.horde-cancel").first().observe("click", function() { window.close(); })'
), true);

IMP::header(_("Save Image"));
IMP::status();
echo $view->render('saveimage');
$page_output->footer();
