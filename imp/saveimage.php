<?php
/**
 * Save an image to a registry-defined application.

 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp');

$vars = Horde_Variables::getDefaultVariables();

/* Run through the action handlers. */
switch ($vars->actionID) {
case 'save_image':
    $contents = $injector->getInstance('IMP_Contents')->getOb(new IMP_Indices($vars->mbox, $vars->uid));
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

if (!$registry->hasMethod('images/selectGalleries') ||
    !$registry->hasMethod('images/saveImage')) {
    throw new IMP_Exception('Image saving is not available.');
}

/* Build the template. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);
$t->set('action', Horde::url('saveimage.php'));
$t->set('id', htmlspecialchars($vars->id));
$t->set('uid', htmlspecialchars($vars->uid));
$t->set('mbox', htmlspecialchars($vars->mbox));
$t->set('image_img', Horde::img('mime/image.png', _("Image")));

/* Build the list of galleries. */
$t->set('gallerylist', $registry->images->selectGalleries(array('perm' => Horde_Perms::EDIT)));

$title = _("Save Image");
require IMP_TEMPLATES . '/common-header.inc';
IMP::status();
echo $t->fetch(IMP_TEMPLATES . '/saveimage/saveimage.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
