<?php
/**
 * Mobile (MIMP) folder tree display page.
 *
 * URL Parameters:
 *   - ts: (integer) Toggle subscribe view.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Anil Madhavapeddy <avsm@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array('impmode' => 'mimp'));

/* Redirect back to the mailbox if folder use is not allowed. */
if (!$injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS)) {
    $notification->push(_("The folder view is not enabled."), 'horde.error');
    Horde::url('mailbox-mimp.php', true)->redirect();
}

/* Decide whether or not to show all the unsubscribed mailboxes. */
$subscribe = $prefs->getValue('subscribe');
$showAll = (!$subscribe || $session->get('imp', 'showunsub'));

/* Initialize the IMP_Imap_Tree object. */
$imptree = $injector->getInstance('IMP_Imap_Tree');
$mask = 0;

/* Toggle subscribed view, if necessary. */
if ($subscribe && Horde_Util::getFormData('ts')) {
    $showAll = !$showAll;
    $session->set('imp', 'showunsub', $showAll);
    $imptree->showUnsubscribed($showAll);
    if ($showAll) {
        $mask |= IMP_Imap_Tree::FLIST_UNSUB;
    }
}

$imptree->setIteratorFilter($mask);
$tree = $imptree->createTree('mimp_folders', array(
    'poll_info' => true,
    'render_type' => 'IMP_Tree_Simplehtml'
));

$selfurl = Horde::url('folders-mimp.php');
$menu = array(array(_("Refresh"), $selfurl));
if ($subscribe) {
    $menu[] = array(
        ($showAll ? _("Show Subscribed Mailboxes") : _("Show All Mailboxes")),
        $selfurl->copy()->add('ts', 1)
    );
}

$title = _("Folders");

$t = $injector->createInstance('Horde_Template');
$t->set('menu', $injector->getInstance('IMP_Ui_Mimp')->getMenu('folders', $menu));
$t->set('title', $title);
$t->set('tree', $tree->getTree(true));

IMP::header($title);
IMP::status();
echo $t->fetch(IMP_TEMPLATES . '/mimp/folders/folders.html');
