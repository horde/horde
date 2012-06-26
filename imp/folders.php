<?php
/**
 * Folder tree display for traditional (IMP) view.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Anil Madhavapeddy <avsm@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp', array(
    'impmode' => Horde_Registry::VIEW_BASIC
));

/* Redirect back to the mailbox if folder use is not allowed. */
$imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
if (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
    $notification->push(_("The folder view is not enabled."), 'horde.error');
    Horde::url('mailbox.php', true)->redirect();
}

/* Decide whether or not to show all the unsubscribed mailboxes. */
$subscribe = $prefs->getValue('subscribe');
$showAll = (!$subscribe || $session->get('imp', 'showunsub'));

$page_output->addScriptFile('folders.js');

$vars = $injector->getInstance('Horde_Variables');

/* Get the base URL for this page. */
$folders_url = Horde::selfUrl();

/* These JS defines are required by all sub-pages. */
$page_output->addInlineJsVars(array(
    'ImpFolders.folders_url' => strval($folders_url),
    'ImpFolders.text' => array(
        'download1' => _("All messages in the following mailbox(es) will be downloaded into one MBOX file:"),
        'download2' => _("This may take some time. Are you sure you want to continue?"),
        'no_rename' => _("This mailbox may not be renamed:"),
        'oneselect' => _("Only one mailbox should be selected for this action."),
        'rename1' => _("You are renaming the mailbox:"),
        'rename2' => _("Please enter the new name:"),
        'select' => _("Please select a mailbox before you perform this action."),
        'subfolder1' => _("You are creating a subfolder to"),
        'subfolder2' => _("Please enter the name of the new mailbox:"),
        'toplevel' => _("You are creating a top-level mailbox.") . "\n" . _("Please enter the name of the new mailbox:")
    )
));

/* Initialize the IMP_Imap_Tree object. */
$imaptree = $injector->getInstance('IMP_Imap_Tree');

/* $mbox_list entries are urlencoded. */
$mbox_list = isset($vars->mbox_list)
    ? IMP_Mailbox::formFrom($vars->mbox_list)
    : array();

/* Token to use in requests */
$folders_token = $injector->getInstance('Horde_Token')->get('imp.folders');

/* META refresh time (might be altered by actionID). */
$refresh_time = $prefs->getValue('refresh_time');

/* Run through the action handlers. */
if ($vars->actionID) {
    try {
        $injector->getInstance('Horde_Token')->validate($vars->folders_token, 'imp.folders');
    } catch (Horde_Token_Exception $e) {
        $notification->push($e);
        $vars->actionID = null;
    }
}

switch ($vars->actionID) {
case 'expand_all_folders':
    $imaptree->expandAll();
    break;

case 'collapse_all_folders':
    $imaptree->collapseAll();
    break;

case 'rebuild_tree':
    $imaptree->init();
    break;

case 'expunge_mbox':
    if (!empty($mbox_list)) {
        $injector->getInstance('IMP_Message')->expungeMailbox(array_fill_keys($mbox_list, null));
    }
    break;

case 'delete_mbox':
    foreach ($mbox_list as $val) {
        $val->delete();
    }
    break;

case 'download_mbox':
case 'download_mbox_zip':
    $registry->downloadUrl('mbox', array(
        'actionID' => 'download_mbox',
        'mbox_list' => $vars->mbox_list,
        'zip' => intval($vars->actionID == 'download_mbox_zip')
    ))->redirect();
    exit;

case 'import_mbox':
    if ($vars->import_mbox) {
        try {
            $notification->push($injector->getInstance('IMP_Ui_Folder')->importMbox($vars->import_mbox, 'mbox_upload'), 'horde.success');
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
        $vars->actionID = null;
    } else {
        $refresh_time = null;
    }
    break;

case 'create_mbox':
    if (isset($vars->new_mailbox)) {
        try {
            $new_mbox = $imaptree->createMailboxName(
                $mbox_list[0],
                $vars->new_mailbox
            );
            if ($new_mbox->exists) {
                $notification->push(sprintf(_("Mailbox \"%s\" already exists."), $new_mbox->display), 'horde.warning');
            } else {
                $new_mbox->create();
            }
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
    }
    break;

case 'rename_mbox':
    // $old_names may be URL encoded.
    $old_names = array_map('trim', explode("\n", $vars->old_names));
    $new_names = array_map('trim', explode("\n", $vars->new_names));

    $iMax = count($new_names);
    if (!empty($new_names) &&
        !empty($old_names) &&
        ($iMax == count($old_names))) {
        for ($i = 0; $i < $iMax; ++$i) {
            $old_name = IMP_Mailbox::formFrom($old_names[$i]);
            $old_ns = $old_name->namespace_info;
            $new = trim($new_names[$i], $old_ns['delimiter']);

            /* If this is a personal namespace, then anything goes as far as
             * the input. Just append the personal namespace to it. */
            if (($old_ns['type'] == Horde_Imap_Client::NS_PERSONAL) ||
                ($old_ns['name'] &&
                 (stripos($new_names[$i], $old_ns['name']) !== 0))) {
                $new = $old_ns['name'] . $new;
            }

            $old_name->rename($new);
        }
    }
    break;

case 'subscribe_mbox':
case 'unsubscribe_mbox':
    if (empty($mbox_list)) {
        $notification->push(_("No mailboxes were specified"), 'horde.message');
    } else {
        foreach ($mbox_list as $val) {
            $val->subscribe($vars->actionID == 'subscribe_mbox');
        }
    }
    break;

case 'toggle_subscribed_view':
    if ($subscribe) {
        $showAll = !$showAll;
        $session->set('imp', 'showunsub', $showAll);
        $imaptree->showUnsubscribed($showAll);
    }
    break;

case 'poll_mbox':
    if (!empty($mbox_list)) {
        $imaptree->addPollList($mbox_list);
    }
    break;

case 'nopoll_mbox':
    if (!empty($mbox_list)) {
        $imaptree->removePollList($mbox_list);
    }
    break;

case 'empty_mbox':
    if (!empty($mbox_list)) {
        $injector->getInstance('IMP_Message')->emptyMailbox($mbox_list);
    }
    break;

case 'mark_mbox_seen':
case 'mark_mbox_unseen':
    if (!empty($mbox_list)) {
        $injector->getInstance('IMP_Message')->flagAllInMailbox(array('\\seen'), $mbox_list, ($vars->actionID == 'mark_mbox_seen'));
    }
    break;

case 'delete_mbox_confirm':
case 'empty_mbox_confirm':
    if (!empty($mbox_list)) {
        $loop = array();
        foreach ($mbox_list as $val) {
            switch ($vars->actionID) {
            case 'delete_mbox_confirm':
                if (!$val->access_deletembox) {
                    $notification->push(sprintf(_("The mailbox \"%s\" may not be deleted."), $val->display), 'horde.error');
                    continue 2;
                }
                break;

            case 'empty_mbox_confirm':
                if (!$val->access_empty) {
                    $notification->push(sprintf(_("The mailbox \"%s\" may not be emptied."), $val->display), 'horde.error');
                    continue 2;
                }
                break;
            }

            try {
                $elt_info = $imp_imap->status($val, Horde_Imap_Client::STATUS_MESSAGES);
            } catch (IMP_Imap_Exception $e) {
                $elt_info = null;
            }

            $data = array(
                'name' => $val->display_html,
                'msgs' => $elt_info ? $elt_info['messages'] : 0,
                'val' => $val->form_to
            );
            $loop[] = $data;
        }

        if (!count($loop)) {
            break;
        }

        $page_output->addScriptFile('stripe.js', 'horde');

        $menu = IMP::menu();
        IMP::header(_("Folder Actions - Confirmation"));
        echo $menu;

        $template = $injector->createInstance('Horde_Template');
        $template->setOption('gettext', true);
        $template->set('delete', ($vars->actionID == 'delete_mbox_confirm'));
        $template->set('empty', ($vars->actionID == 'empty_mbox_confirm'));
        $template->set('mboxes', $loop);
        $template->set('folders_url', $folders_url);
        $template->set('folders_token', $folders_token);
        echo $template->fetch(IMP_TEMPLATES . '/imp/folders/folders_confirm.html');

        $page_output->footer();
        exit;
    }
    break;

case 'mbox_size':
    if (!empty($mbox_list)) {
        $loop = array();
        $sum = 0;

        $imp_message = $injector->getInstance('IMP_Message');

        foreach ($mbox_list as $val) {
            $size = $imp_message->sizeMailbox($val, false);
            $data = array(
                'name' => $val->display_html,
                'size' => sprintf(_("%.2fMB"), $size / (1024 * 1024)),
                'sort' => $size
            );
            $sum += $size;
            $loop[] = $data;
        }

        $template = $injector->createInstance('Horde_Template');
        $template->setOption('gettext', true);
        $template->set('mboxes', $loop);
        $template->set('mboxes_sum', sprintf(_("%.2fMB"), $sum / (1024 * 1024)));
        $template->set('folders_url', $folders_url);

        $page_output->addScriptFile('stripe.js', 'horde');
        $page_output->addScriptFile('tables.js', 'horde');

        $menu = IMP::menu();
        IMP::header(_("Mailbox Sizes"));
        echo $menu;
        IMP::status();
        IMP::quota();

        echo $template->fetch(IMP_TEMPLATES . '/imp/folders/folders_size.html');

        $page_output->footer();
        exit;
    }
    break;

case 'search':
    if (!empty($mbox_list)) {
        $url = new Horde_Url(Horde::url('search.php'));
        $url->add('subfolder', 1)
            ->add('mailbox_list', IMP_Mailbox::formTo($mbox_list))
            ->redirect();
    }
    break;
}

$folders_url_ob = new Horde_Url($folders_url);
$folders_url_ob->add('folders_token', $folders_token);

if ($session->get('imp', 'file_upload') &&
    ($vars->actionID == 'import_mbox')) {
    $menu = IMP::menu();
    IMP::header(_("Folder Navigator"));
    echo $menu;
    IMP::status();
    IMP::quota();

    /* Prepare import template. */
    $i_template = $injector->createInstance('Horde_Template');
    $i_template->setOption('gettext', true);
    $i_template->set('folders_url', $folders_url_ob);
    $i_template->set('import_mbox', htmlspecialchars($mbox_list[0]));
    $i_template->set('folders_token', $folders_token);
    echo $i_template->fetch(IMP_TEMPLATES . '/imp/folders/import.html');
    $page_output->footer();
    exit;
}

/* Prepare the sidebar. */
$refresh_title = _("Reload View");
$refresh_ak = Horde::getAccessKey($refresh_title);
$refresh_title = Horde::stripAccessKey($refresh_title);
if (!empty($refresh_ak)) {
    $refresh_title .= sprintf(_(" (Accesskey %s)"), $refresh_ak);
}
$sidebar = $injector->getInstance('Horde_View_Sidebar');
$sidebar->newRefresh = $folders_url_ob->link(array(
    'accesskey' => $refresh_ak,
    'title' => $refresh_title
));

/* Prepare the header template. */
$head_template = $injector->createInstance('Horde_Template');
$head_template->setOption('gettext', true);
$head_template->set('title', $refresh_title);
$head_template->set('folders_url', $folders_url_ob);
$head_template->set('folders_token', $folders_token);

/* Prepare the actions template. */
$a_template = $injector->createInstance('Horde_Template');
$a_template->setOption('gettext', true);
$a_template->set('id', 0);

$a_template->set('check_ak', Horde::getAccessKeyAndTitle(_("Check _All/None")));
$a_template->set('create_mbox', $injector->getInstance('Horde_Core_Perms')->hasAppPermission('create_folders') && $injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_folders'));
if ($prefs->getValue('subscribe')) {
    $a_template->set('subscribe', true);
    $subToggleText = ($showAll) ? _("Hide Unsubscribed") : _("Show All");
    $a_template->set('toggle_subscribe', Horde::widget($folders_url_ob->copy()->add(array('actionID' => 'toggle_subscribed_view', 'folders_token' => $folders_token)), $subToggleText, '', '', '', $subToggleText, true));
}
$a_template->set('nav_poll', !$prefs->isLocked('nav_poll') && !$prefs->getValue('nav_poll_all'));
$a_template->set('notrash', !$prefs->getValue('use_trash'));
$a_template->set('file_upload', $session->get('imp', 'file_upload'));
$a_template->set('expand_all', Horde::widget($folders_url_ob->copy()->add(array('actionID' => 'expand_all_folders', 'folders_token' => $folders_token)), _("Expand All Folders"), '', '', '', _("Expand All"), true));
$a_template->set('collapse_all', Horde::widget($folders_url_ob->copy()->add(array('actionID' => 'collapse_all_folders', 'folders_token' => $folders_token)), _("Collapse All Folders"), '', '', '', _("Collapse All"), true));
$a_template->set('help', Horde_Help::link('imp', 'folder-options'));

/* Build the folder tree. */
$imaptree->setIteratorFilter(IMP_Imap_Tree::FLIST_VFOLDER);
$tree = $imaptree->createTree('imp_folders', array(
    'checkbox' => true,
    'editvfolder' => true,
    'poll_info' => true
));

$displayNames = $fullNames = array();

foreach ($imaptree as $key => $val) {
    $tmp = $displayNames[] = $val->display;

    $tmp2 = $val->display_notranslate;
    if ($tmp != $tmp2) {
        $fullNames[$key] = $tmp2;
    }
}

$page_output->addInlineJsVars(array(
    'ImpFolders.ajax' => $registry->getServiceLink('ajax', 'imp')->url,
    'ImpFolders.displayNames' => $displayNames,
    'ImpFolders.fullNames' => $fullNames,
    '-ImpFolders.mbox_expand' => intval($prefs->getValue('nav_expanded') == 2)
));

$menu = IMP::menu();
$page_output->metaRefresh($refresh_time, Horde::url('folders.php', true));
IMP::header(_("Folder Navigator"));
echo $menu;
IMP::status();
IMP::quota();

echo $head_template->fetch(IMP_TEMPLATES . '/imp/folders/head.html');
echo $a_template->fetch(IMP_TEMPLATES . '/imp/folders/actions.html');
$tree->renderTree();
if (count($tree) > 10) {
    $a_template->set('id', 1);
    echo $a_template->fetch(IMP_TEMPLATES . '/imp/folders/actions.html');
}

/* No need for extra template - close out the tags here. */
echo '</form>';

$page_output->footer();
