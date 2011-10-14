<?php
/**
 * jQuery Mobile page.
 *
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array('impmode' => 'mobile'));

$title = _("Mobile Mail");
require $registry->get('templates', 'horde') . '/common-header-mobile.inc';

$view = new Horde_View(array('templatePath' => IMP_TEMPLATES . '/mobile'));
new Horde_View_Helper_Text($view);

/* Initialize the IMP_Imap_Tree object. */
$imptree = $injector->getInstance('IMP_Imap_Tree');
$imptree->setIteratorFilter();
$tree = $imptree->createTree('mobile_folders', array(
    'poll_info' => true,
    'render_type' => 'IMP_Tree_Jquerymobile'
));
$view->tree = $tree->getTree(true);

$view->portal = Horde::getServiceLink('portal', 'horde')->setRaw(false);
$view->logout = Horde::getServiceLink('logout')->setRaw(false);

/* Setting up identities. */
$identity = $injector->getInstance('IMP_Identity');
$view->identities = array();
foreach ($identity->getSelectList() as $id => $from) {
    $view->identities[] = array(
        'label' => htmlspecialchars($from),
        'sel' => $id == $identity->getDefault(),
        'val' => htmlspecialchars($id)
    );
}

$imp_compose = $injector->getInstance('IMP_Factory_Compose')->create();
$view->composeLink = Horde::getServiceLink('ajax', 'imp');
$view->composeLink->pathInfo = 'addAttachment';
$view->composeCache = $imp_compose->getCacheId();

echo $view->render('head.html.php');
echo $view->render('folders.html.php');
echo $view->render('mailbox.html.php');
echo $view->render('message.html.php');
echo $view->render('compose.html.php');
echo $view->render('notice.html.php');
require $registry->get('templates', 'horde') . '/common-footer-mobile.inc';
