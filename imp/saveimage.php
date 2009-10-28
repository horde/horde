<?php
/**
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => true));

$id = Horde_Util::getFormData('id');
$muid = Horde_Util::getFormData('muid');

/* Run through the action handlers. */
$actionID = Horde_Util::getFormData('actionID');
switch ($actionID) {
case 'save_image':
    $contents = IMP_Contents::singleton($muid);
    $mime_part = $contents->getMIMEPart($id);
    $image_data = array(
        'filename' => $mime_part->getName(true),
        'description' => $mime_part->getDescription(true),
        'data' => $mime_part->getContents(),
        'type' => $mime_part->getType()
    );
    try {
        $registry->call('images/saveImage', array(null, Horde_Util::getFormData('gallery'), $image_data));
    } catch (Horde_Exception $e) {
        $notification->push($e, 'horde.error');
        break;
    }
    Horde_Util::closeWindowJS();
    exit;
}

/* Build the template. */
$t = new Horde_Template();
$t->setOption('gettext', true);
$t->set('action', Horde::applicationUrl('saveimage.php'));
$t->set('id', htmlspecialchars($id));
$t->set('muid', htmlspecialchars($muid));
$t->set('image_img', Horde::img('mime/image.png', _("Image"), null, $registry->getImageDir('horde')));

/* Build the list of galleries. */
$t->set('gallerylist', $registry->call('images/selectGalleries', array(null, PERMS_EDIT)));

$title = _("Save Image");
require IMP_TEMPLATES . '/common-header.inc';
IMP::status();
echo $t->fetch(IMP_TEMPLATES . '/saveimage/saveimage.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
