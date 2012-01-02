<?php
/**
 * Kronolith Mobile View
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package Kronolith
 */
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

$title = _("My Calendar");

$view = new Horde_View(array('templatePath' => KRONOLITH_TEMPLATES . '/mobile'));
$view->today = new Horde_Date($_SERVER['REQUEST_TIME']);
$view->registry = $registry;
$view->portal = Horde::getServiceLink('portal', 'horde')->setRaw(false);
$view->logout = Horde::getServiceLink('logout')->setRaw(false);

require $registry->get('templates', 'horde') . '/common-header-mobile.inc';

echo $view->render('head');
echo $view->render('day');
echo $view->render('event');
echo $view->render('month');
echo $view->render('summary');
echo $view->render('notice');
$registry->get('templates', 'horde') . '/common-footer-mobile.inc';
