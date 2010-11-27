<?php
/**
 * Kronolith Mobile View
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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

$datejs = str_replace('_', '-', $GLOBALS['language']) . '.js';
if (!file_exists($GLOBALS['registry']->get('jsfs', 'horde') . '/date/' . $datejs)) {
    $datejs = 'en-US.js';
}
Horde::addScriptFile('horde-jquery.js', 'horde');
Horde::addScriptFile('date/' . $datejs, 'horde');
Horde::addScriptFile('date/date.js', 'horde');
Horde::addScriptFile('mobile.js', 'kronolith');

require $registry->get('templates', 'horde') . '/common-header-mobile.inc';
require KRONOLITH_TEMPLATES . '/mobile/javascript_defs.php';
echo $view->render('head');
echo $view->render('day');
echo $view->render('event');
echo $view->render('month');
echo $view->render('summary');
echo $view->render('notice');
$registry->get('templates', 'horde') . '/common-footer-mobile.inc';
