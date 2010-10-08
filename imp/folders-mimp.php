<?php
/**
 * Mobile (MIMP) folder display page.
 *
 * URL Parameters:
 *   'ts' = (integer) Toggle subscribe view.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Anil Madhavapeddy <avsm@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array('impmode' => 'mimp'));

/* Redirect back to the mailbox if folder use is not allowed. */
if (empty($conf['user']['allow_folders'])) {
    $notification->push(_("Folder use is not enabled."), 'horde.error');
    Horde::url('mailbox-mimp.php', true)->redirect();
}

/* Decide whether or not to show all the unsubscribed folders */
$subscribe = $prefs->getValue('subscribe');
$showAll = (!$subscribe || $session['imp:showunsub']);

/* Initialize the IMP_Imap_Tree object. */
$imptree = $injector->getInstance('IMP_Imap_Tree');
$mask = 0;

/* Toggle subscribed view, if necessary. */
if ($subscribe && Horde_Util::getFormData('ts')) {
    $showAll = !$showAll;
    $session['imp:showunsub'] = $showAll;
    $imptree->showUnsubscribed($showAll);
    if ($showAll) {
        $mask |= IMP_Imap_Tree::FLIST_UNSUB;
    }
}

$imptree->setIteratorFilter($mask);
$tree = $imptree->createTree('mimp_folders', array(
    'poll_info' => true,
    'render_type' => 'Simplehtml'
));

$selfurl = Horde::url('folders-mimp.php');
$menu = array(array(_("Refresh"), $selfurl));
if ($subscribe) {
    $menu[] = array(
        ($showAll ? _("Show Subscribed Folders") : _("Show All Folders")),
        $selfurl->copy()->add('ts', 1)
    );
}

$title = _("Folders");

$t = $injector->createInstance('Horde_Template');
$t->set('menu', $injector->getInstance('IMP_Ui_Mimp')->getMenu('folders', $menu));
$t->set('title', $title);
$t->set('tree', $tree->getTree(true));

require_once IMP_TEMPLATES . '/common-header.inc';
IMP::status();
echo $t->fetch(IMP_TEMPLATES . '/mimp/folders/folders.html');
