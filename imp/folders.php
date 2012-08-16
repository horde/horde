<?php
/**
 * Folders display for traditional (IMP) view.
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

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array(
    'impmode' => 'imp'
));

Horde::addScriptFile('folders.js', 'imp');

/* Redirect back to the mailbox if folder use is not allowed. */
$imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
if (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
    $notification->push(_("Folder use is not enabled."), 'horde.error');
    Horde::url('mailbox.php', true)->redirect();
}

/* Decide whether or not to show all the unsubscribed folders */
$subscribe = $prefs->getValue('subscribe');
$showAll = (!$subscribe || $session->get('imp', 'showunsub'));

$vars = Horde_Variables::getDefaultVariables();

/* Get the base URL for this page. */
$folders_url = Horde::selfUrl();

/* This JS define is required by all folder pages. */
Horde::addInlineJsVars(array(
    'ImpFolders.folders_url' => strval($folders_url)
));

/* Initialize the IMP_Imap_Tree object. */
$imaptree = $injector->getInstance('IMP_Imap_Tree');

/* $folder_list is already encoded in UTF7-IMAP, but entries are
 * urlencoded. */
$folder_list = isset($vars->folder_list)
    ? IMP_Mailbox::formFrom($vars->folder_list)
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

case 'expunge_folder':
    if (!empty($folder_list)) {
        $injector->getInstance('IMP_Message')->expungeMailbox(array_fill_keys($folder_list, null));
    }
    break;

case 'delete_folder':
    foreach ($folder_list as $val) {
        $val->delete();
    }
    break;

case 'download_folder':
case 'download_folder_zip':
    if (!empty($folder_list)) {
        try {
            $injector->getInstance('IMP_Ui_Folder')->downloadMbox($folder_list, $vars->actionID == 'download_folder_zip');
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
    }
    break;

case 'import_mbox':
    if ($vars->import_folder) {
        try {
            $notification->push($injector->getInstance('IMP_Ui_Folder')->importMbox($vars->import_folder, 'mbox_upload'), 'horde.success');
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
        $vars->actionID = null;
    } else {
        $refresh_time = null;
    }
    break;

case 'create_folder':
    if (isset($vars->new_mailbox)) {
        try {
            $new_mbox = $imaptree->createMailboxName(
                empty($folder_list) ? null : $folder_list[0],
                Horde_String::convertCharset($vars->new_mailbox, 'UTF-8', 'UTF7-IMAP')
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

case 'rename_folder':
    // $old_names already in UTF7-IMAP, but may be URL encoded.
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

            $old_name->rename(Horde_String::convertCharset($new, 'UTF-8', 'UTF7-IMAP'));
        }
    }
    break;

case 'subscribe_folder':
case 'unsubscribe_folder':
    if (empty($folder_list)) {
        $notification->push(_("No folders were specified"), 'horde.message');
    } else {
        foreach ($folder_list as $val) {
            $val->subscribe($vars->actionID == 'subscribe_folder');
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

case 'poll_folder':
    if (!empty($folder_list)) {
        $imaptree->addPollList($folder_list);
    }
    break;

case 'nopoll_folder':
    if (!empty($folder_list)) {
        $imaptree->removePollList($folder_list);
    }
    break;

case 'folders_empty_mailbox':
    if (!empty($folder_list)) {
        $injector->getInstance('IMP_Message')->emptyMailbox($folder_list);
    }
    break;

case 'mark_folder_seen':
case 'mark_folder_unseen':
    if (!empty($folder_list)) {
        $injector->getInstance('IMP_Message')->flagAllInMailbox(array('\\seen'), $folder_list, ($vars->actionID == 'mark_folder_seen'));
    }
    break;

case 'delete_folder_confirm':
case 'folders_empty_mailbox_confirm':
    if (!empty($folder_list)) {
        $loop = array();
        $rowct = 0;
        foreach ($folder_list as $val) {
            switch ($vars->actionID) {
            case 'delete_folder_confirm':
                if ($val->fixed || !$val->access_deletembox) {
                    $notification->push(sprintf(_("The folder \"%s\" may not be deleted."), $val->display), 'horde.error');
                    continue 2;
                }
                break;

            case 'folders_empty_mailbox_confirm':
                if (!$val->access_deletemsgs || !$val->access_expunge) {
                    $notification->push(sprintf(_("The folder \"%s\" may not be emptied."), $val->display), 'horde.error');
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
                'class' => 'item' . (++$rowct % 2),
                'name' => $val->display_html,
                'msgs' => $elt_info ? $elt_info['messages'] : 0,
                'val' => $val->form_to
            );
            $loop[] = $data;
        }

        if (!count($loop)) {
            break;
        }

        $title = _("Folder Actions - Confirmation");
        $menu = IMP::menu();
        require IMP_TEMPLATES . '/common-header.inc';
        echo $menu;

        $template = $injector->createInstance('Horde_Template');
        $template->setOption('gettext', true);
        $template->set('delete', ($vars->actionID == 'delete_folder_confirm'));
        $template->set('empty', ($vars->actionID == 'folders_empty_mailbox_confirm'));
        $template->set('folders', $loop);
        $template->set('folders_url', $folders_url);
        $template->set('folders_token', $folders_token);
        echo $template->fetch(IMP_TEMPLATES . '/imp/folders/folders_confirm.html');

        require $registry->get('templates', 'horde') . '/common-footer.inc';
        exit;
    }
    break;

case 'mbox_size':
    if (!empty($folder_list)) {
        Horde::addScriptFile('tables.js', 'horde');

        $title = _("Folder Sizes");
        $menu = IMP::menu();
        require IMP_TEMPLATES . '/common-header.inc';
        echo $menu;
        IMP::status();
        IMP::quota();

        $loop = array();
        $rowct = $sum = 0;

        $imp_message = $injector->getInstance('IMP_Message');

        foreach ($folder_list as $val) {
            $size = $imp_message->sizeMailbox($val, false);
            $data = array(
                'class' => 'item' . (++$rowct % 2),
                'name' => $val->display_html,
                'size' => sprintf(_("%.2fMB"), $size / (1024 * 1024)),
                'sort' => $size
            );
            $sum += $size;
            $loop[] = $data;
        }

        $template = $injector->createInstance('Horde_Template');
        $template->setOption('gettext', true);
        $template->set('folders', $loop);
        $template->set('folders_url', $folders_url);
        $template->set('folders_sum', sprintf(_("%.2fMB"), $sum / (1024 * 1024)));
        echo $template->fetch(IMP_TEMPLATES . '/imp/folders/folders_size.html');

        require $registry->get('templates', 'horde') . '/common-footer.inc';
        exit;
    }
    break;

case 'search':
    if (!empty($folder_list)) {
        $url = new Horde_Url(Horde::url('search.php'));
        $url->add('subfolder', 1)
            ->add('mailbox_list', IMP_Mailbox::formTo($folder_list))
            ->redirect();
    }
    break;
}

$folders_url_ob = new Horde_Url($folders_url);
$folders_url_ob->add('folders_token', $folders_token);

if ($session->get('imp', 'file_upload') &&
    ($vars->actionID == 'import_mbox')) {
    $title = _("Folder Navigator");
    $menu = IMP::menu();
    require IMP_TEMPLATES . '/common-header.inc';
    echo $menu;
    IMP::status();
    IMP::quota();

    /* Prepare import template. */
    $i_template = $injector->createInstance('Horde_Template');
    $i_template->setOption('gettext', true);
    $i_template->set('folders_url', $folders_url_ob);
    $i_template->set('import_folder', $folder_list[0]);
    $i_template->set('folder_name', htmlspecialchars(Horde_String::convertCharset($folder_list[0], 'UTF7-IMAP', 'UTF-8')));
    $i_template->set('folders_token', $folders_token);
    echo $i_template->fetch(IMP_TEMPLATES . '/imp/folders/import.html');
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

/* Prepare the header template. */
$refresh_title = _("Reload View");
$head_template = $injector->createInstance('Horde_Template');
$head_template->setOption('gettext', true);
$head_template->set('title', $refresh_title);
$head_template->set('folders_url', $folders_url_ob);
$refresh_ak = Horde::getAccessKey($refresh_title);
$refresh_title = Horde::stripAccessKey($refresh_title);
if (!empty($refresh_ak)) {
    $refresh_title .= sprintf(_(" (Accesskey %s)"), $refresh_ak);
}
$head_template->set('refresh', $folders_url_ob->link(array(
    'accesskey' => $refresh_ak,
    'title' => $refresh_title
)));
$head_template->set('help', Horde_Help::link('imp', 'folder-options'));
$head_template->set('folders_token', $folders_token);

/* Prepare the actions template. */
$a_template = $injector->createInstance('Horde_Template');
$a_template->setOption('gettext', true);
$a_template->set('id', 0);
$a_template->set('javascript', $browser->hasFeature('javascript'));

if ($a_template->get('javascript')) {
    $a_template->set('check_ak', Horde::getAccessKeyAndTitle(_("Check _All/None")));
} else {
    $a_template->set('go', _("Go"));
}

$a_template->set('create_folder', $injector->getInstance('Horde_Core_Perms')->hasAppPermission('create_folders') && $injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_folders'));
if ($prefs->getValue('subscribe')) {
    $a_template->set('subscribe', true);
    $subToggleText = ($showAll) ? _("Hide Unsubscribed") : _("Show All Folders");
    $a_template->set('toggle_subscribe', Horde::widget($folders_url_ob->copy()->add(array('actionID' => 'toggle_subscribed_view', 'folders_token' => $folders_token)), $subToggleText, 'widget', '', '', $subToggleText, true));
}
$a_template->set('nav_poll', !$prefs->isLocked('nav_poll') && !$prefs->getValue('nav_poll_all'));
$a_template->set('notrash', !$prefs->getValue('use_trash'));
$a_template->set('file_upload', $session->get('imp', 'file_upload'));
$a_template->set('expand_all', Horde::widget($folders_url_ob->copy()->add(array('actionID' => 'expand_all_folders', 'folders_token' => $folders_token)), _("Expand All Folders"), 'widget', '', '', _("Expand All"), true));
$a_template->set('collapse_all', Horde::widget($folders_url_ob->copy()->add(array('actionID' => 'collapse_all_folders', 'folders_token' => $folders_token)), _("Collapse All Folders"), 'widget', '', '', _("Collapse All"), true));

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

Horde::addInlineJsVars(array(
    'ImpFolders.ajax' => Horde::getServiceLink('ajax', 'imp')->url,
    'ImpFolders.displayNames' => $displayNames,
    'ImpFolders.fullNames' => $fullNames,
    '-ImpFolders.mbox_expand' => intval($prefs->getValue('nav_expanded') == 2)
));

$title = _("Folder Navigator");
$menu = IMP::menu();
Horde::metaRefresh($refresh_time, Horde::url('folders.php', true));
require IMP_TEMPLATES . '/common-header.inc';
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
echo '</form></div>';

require $registry->get('templates', 'horde') . '/common-footer.inc';
