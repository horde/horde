<?php
/**
 * Ansel Mobile View
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package Ansel
 */
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

$title = _("Photo Galleries");

$view = new Horde_View(array('templatePath' => ANSEL_TEMPLATES . '/mobile'));
$view->registry = $registry;
$view->portal = Horde::getServiceLink('portal', 'horde')->setRaw(false);
$view->logout = Horde::getServiceLink('logout')->setRaw(false);

require $registry->get('templates', 'horde') . '/common-header-mobile.inc';

echo $view->render('head');
echo $view->render('galleries');
//echo $view->render('gallery');
//echo $view->render('photo');
echo $view->render('notice');
$registry->get('templates', 'horde') . '/common-footer-mobile.inc';
