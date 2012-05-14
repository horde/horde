<?php
/**
 * Ansel smartmobile view.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package Ansel
 */
require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ansel');

$title = _("Photo Galleries");

$view = new Horde_View(array('templatePath' => ANSEL_TEMPLATES . '/smartmobile'));
$view->registry = $registry;
$view->portal = $registry->getServiceLink('portal')->setRaw(false);
$view->logout = $registry->getServiceLink('logout')->setRaw(false);

require ANSEL_TEMPLATES . '/smartmobile/javascript_defs.php';
$page_output->addScriptFile('smartmobile.js');
// TODO: Figure out how to force load the gallerylist page.
$page_output->header(array(
    'title' => $title,
    'view' => $registry::VIEW_SMARTMOBILE
));

echo $view->render('galleries');
echo $view->render('gallery');
echo $view->render('image');
//echo $view->render('photo');

$page_output->footer();
