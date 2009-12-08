<?php
/**
 * Minimalist folder display page.
 *
 * URL Parameters:
 *   'ts' = (integer) Toggle subscribe view.
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => true));

/* Redirect back to the mailbox if folder use is not allowed. */
if (empty($conf['user']['allow_folders'])) {
    $notification->push(_("Folder use is not enabled."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('mailbox-mimp.php', true));
    exit;
}

/* Decide whether or not to show all the unsubscribed folders */
$subscribe = $prefs->getValue('subscribe');
$showAll = (!$subscribe || $_SESSION['imp']['showunsub']);

/* Initialize the IMP_Imap_Tree object. */
$imptree = IMP_Imap_Tree::singleton();
$mask = IMP_Imap_Tree::NEXT_SHOWCLOSED;

/* Toggle subscribed view, if necessary. */
if ($subscribe && Horde_Util::getFormData('ts')) {
    $showAll = !$showAll;
    $_SESSION['imp']['showunsub'] = $showAll;
    $imptree->showUnsubscribed($showAll);
    $mask |= IMP_Imap_Tree::NEXT_SHOWSUB;
}

/* Start iterating through the list of mailboxes, displaying them. */
$rows = array();
$tree_ob = $imptree->build($mask);
foreach ($tree_ob[0] as $val) {
    $rows[] = array(
        'level' => str_repeat('..', $val['level']),
        'label' => $val['base_elt']['l'],
        'link' => ((empty($val['container'])) ? IMP::generateIMPUrl('mailbox-mimp.php', $val['value']) : null),
        'msgs' => ((isset($val['msgs'])) ? ($val['unseen'] . '/' . $val['msgs']) : null)
    );
}

$selfurl = Horde::applicationUrl('folders-mimp.php');
if ($subscribe) {
    $sub_text = $showAll ? _("Show Subscribed Folders") : _("Show All Folders");
    $sub_link = $selfurl->copy()->add('ts', 1);
}

$title = _("Folders");
require IMP_TEMPLATES . '/folders/folders-mimp.inc';
