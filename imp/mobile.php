<?php
/**
 * jQuery Mobile page.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array('impmode' => 'mimp'));

$view = new Horde_View(array('templatePath' => IMP_TEMPLATES . '/mobile'));
new Horde_View_Helper_Text($view);
$view->logout = Horde::getServiceLink('logout')->setRaw(false);

$title = _("Mobile Mail");

require $registry->get('templates', 'horde') . '/common-header-mobile.inc';
echo $view->render('head.html.php');
echo $view->render('folders.html.php');
echo $view->render('mailbox.html.php');
echo $view->render('message.html.php');
require $registry->get('templates', 'horde') . '/common-footer-mobile.inc';
