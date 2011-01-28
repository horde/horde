<?php
/**
 * jQuery Mobile page.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
Horde_Registry::appInit('imp', array('impmode' => 'mobile'));

$title = _("Mobile Mail");
require $registry->get('templates', 'horde') . '/common-header-mobile.inc';

$view = new Horde_View(array('templatePath' => IMP_TEMPLATES . '/mobile'));
new Horde_View_Helper_Text($view);

if (!$injector->getInstance('IMP_Factory_Imap')->create()->allowFolders()) {
    $view->allowFolders = false;
} else {
    $view->allowFolders = true;

    /* Initialize the IMP_Imap_Tree object. */
    $imptree = $injector->getInstance('IMP_Imap_Tree');
    $imptree->setIteratorFilter(IMP_Imap_Tree::FLIST_NOCONTAINER);
    $tree = $imptree->createTree('mobile_folders', array(
        'poll_info' => true,
        'render_type' => 'Jquerymobile'
    ));
    $view->tree = $tree->getTree(true);
}

$view->portal = Horde::getServiceLink('portal', 'horde')->setRaw(false);
$view->logout = Horde::getServiceLink('logout')->setRaw(false);

echo $view->render('head.html.php');
if ($view->allowFolders) {
    echo $view->render('folders.html.php');
}
echo $view->render('mailbox.html.php');
echo $view->render('message.html.php');
echo $view->render('notice.html.php');
require $registry->get('templates', 'horde') . '/common-footer-mobile.inc';
