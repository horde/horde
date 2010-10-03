<?php
/**
 * Folders display for traditional (IMP) view.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Anil Madhavapeddy <avsm@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp');

Horde::addScriptFile('folders.js', 'imp');

/* Redirect back to the mailbox if folder use is not allowed. */
if (!$conf['user']['allow_folders']) {
    $notification->push(_("Folder use is not enabled."), 'horde.error');
    Horde::url('mailbox.php', true)->redirect();
}

/* Decide whether or not to show all the unsubscribed folders */
$subscribe = $prefs->getValue('subscribe');
$showAll = (!$subscribe || $_SESSION['imp']['showunsub']);

$vars = Horde_Variables::getDefaultVariables();

/* Get the base URL for this page. */
$folders_url = Horde::selfUrl();

/* This JS define is required by all folder pages. */
Horde::addInlineJsVars(array(
    'ImpFolders.folders_url' => strval($folders_url)
));

/* Initialize the IMP_Folder object. */
$imp_folder = $injector->getInstance('IMP_Folder');

/* Initialize the IMP_Imap_Tree object. */
$imaptree = $injector->getInstance('IMP_Imap_Tree');

/* $folder_list is already encoded in UTF7-IMAP, but entries are urlencoded. */
$folder_list = array();
if (isset($vars->folder_list)) {
    foreach ($vars->folder_list as $val) {
        $folder_list[] = IMP::formMbox($val, false);
    }
}

/* META refresh time (might be altered by actionID). */
$refresh_time = $prefs->getValue('refresh_time');

/* Run through the action handlers. */
if ($vars->actionID) {
    try {
        Horde::checkRequestToken('imp.folders', $vars->folders_token);
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $vars->actionID = null;
    }
}

switch ($vars->actionID) {
case 'collapse_folder':
case 'expand_folder':
    if ($vars->folder) {
        ($vars->actionID == 'expand_folder') ? $imaptree->expand($vars->folder) : $imaptree->collapse($vars->folder);
    }
    break;

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
        $injector->getInstance('IMP_Message')->expungeMailbox(array_flip($folder_list));
    }
    break;

case 'delete_folder':
    if (!empty($folder_list)) {
        $imp_folder->delete($folder_list);
    }
    break;

case 'download_folder':
case 'download_folder_zip':
    if (!empty($folder_list)) {
        $mbox = $imp_folder->generateMbox($folder_list);
        if ($vars->actionID == 'download_folder') {
            $data = $mbox;
            fseek($data, 0, SEEK_END);
            $browser->downloadHeaders($folder_list[0] . '.mbox', null, false, ftell($data));
        } else {
            $horde_compress = Horde_Compress::factory('zip');
            try {
                $data = $horde_compress->compress(array(array('data' => $mbox, 'name' => $folder_list[0] . '.mbox')), array('stream' => true));
                fclose($mbox);
            } catch (Horde_Exception $e) {
                fclose($mbox);
                $notification->push($e);
                break;
            }
            fseek($data, 0, SEEK_END);
            $browser->downloadHeaders($folder_list[0] . '.zip', 'application/zip', false, ftell($data));
        }
        rewind($data);
        fpassthru($data);
        exit;
    }
    break;

case 'import_mbox':
    if ($vars->import_folder) {
        try {
            $browser->wasFileUploaded('mbox_upload', _("mailbox file"));
            $res = $imp_folder->importMbox(Horde_String::convertCharset($vars->import_folder, 'UTF-8', 'UTF7-IMAP'), $_FILES['mbox_upload']['tmp_name']);
            $mbox_name = basename(Horde_Util::dispelMagicQuotes($_FILES['mbox_upload']['name']));
            if ($res === false) {
                $notification->push(sprintf(_("There was an error importing %s."), $mbox_name), 'horde.error');
            } else {
                $notification->push(sprintf(_("Imported %d messages from %s."), $res, $mbox_name), 'horde.success');
            }
        } catch (Horde_Browser_Exception $e) {
            $notification->push($e);
        }
        $vars->actionID = null;
    } else {
        $refresh_time = null;
    }
    break;

case 'create_folder':
    if ($vars->new_mailbox) {
        try {
            $new_mailbox = $imaptree->createMailboxName(array_shift($folder_list), Horde_String::convertCharset($vars->new_mailbox, 'UTF-8', 'UTF7-IMAP'));
            $imp_folder->create($new_mailbox, $subscribe);
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
        $imp_imap = $injector->getInstance('IMP_Imap')->getOb();
        for ($i = 0; $i < $iMax; ++$i) {
            $old_name = IMP::formMbox($old_names[$i], false);
            $old_ns = $imp_imap->getNamespace($old_name);
            $new = trim($new_names[$i], $old_ns['delimiter']);

            /* If this is a personal namespace, then anything goes as far as
             * the input. Just append the personal namespace to it. For
             * others, add the  */
            if (($old_ns['type'] == 'personal') ||
                ($old_ns['name'] &&
                 (stripos($new_names[$i], $old_ns['name']) !== 0))) {
                $new = $old_ns['name'] . $new;
            }

            $imp_folder->rename($old_name, Horde_String::convertCharset($new, 'UTF-8', 'UTF7-IMAP'));
        }
    }
    break;

case 'subscribe_folder':
case 'unsubscribe_folder':
    if (!empty($folder_list)) {
        if ($vars->actionID == 'subscribe_folder') {
            $imp_folder->subscribe($folder_list);
        } else {
            $imp_folder->unsubscribe($folder_list);
        }
    } else {
        $notification->push(_("No folders were specified"), 'horde.message');
    }
    break;

case 'toggle_subscribed_view':
    if ($subscribe) {
        $showAll = !$showAll;
        $_SESSION['imp']['showunsub'] = $showAll;
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
        $injector->getInstance('IMP_Message')->flagAllInMailbox(array('seen'), $folder_list, ($vars->actionID == 'mark_folder_seen'));
    }
    break;

case 'delete_folder_confirm':
case 'folders_empty_mailbox_confirm':
    if (!empty($folder_list)) {
        $loop = array();
        $rowct = 0;
        foreach ($folder_list as $val) {
            if (($vars->actionID == 'delete_folder_confirm') &&
                !empty($conf['server']['fixed_folders']) &&
                in_array(IMP::folderPref($val, false), $conf['server']['fixed_folders'])) {
                $notification->push(sprintf(_("The folder \"%s\" may not be deleted."), IMP::displayFolder($val)), 'horde.error');
                continue;
            }

            try {
                $elt_info = $injector->getInstance('IMP_Imap')->getOb()->status($val, Horde_Imap_Client::STATUS_MESSAGES);
            } catch (Horde_Imap_Client_Exception $e) {
                $elt_info = null;
            }

            $data = array(
                'class' => 'item' . (++$rowct % 2),
                'name' => htmlspecialchars(IMP::displayFolder($val)),
                'msgs' => $elt_info ? $elt_info['messages'] : 0,
                'val' => htmlspecialchars($val)
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
        $template->set('folders_token', Horde::getRequestToken('imp.folders'));
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
                'name' => htmlspecialchars(IMP::displayFolder($val)),
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
}

/* Token to use in requests */
$folders_token = Horde::getRequestToken('imp.folders');

$folders_url_ob = new Horde_Url($folders_url);
$folders_url_ob->add('folders_token', $folders_token);

if ($_SESSION['imp']['file_upload'] && ($vars->actionID == 'import_mbox')) {
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

$a_template->set('create_folder', !empty($conf['hooks']['permsdenied']) || ($injector->getInstance('Horde_Perms')->hasAppPermission('create_folders') && $injector->getInstance('Horde_Perms')->hasAppPermission('max_folders')));
if ($prefs->getValue('subscribe')) {
    $a_template->set('subscribe', true);
    $subToggleText = ($showAll) ? _("Hide Unsubscribed") : _("Show Unsubscribed");
    $a_template->set('toggle_subscribe', Horde::widget($folders_url_ob->copy()->add(array('actionID' => 'toggle_subscribed_view', 'folders_token' => $folders_token)), $subToggleText, 'widget', '', '', $subToggleText, true));
}
$a_template->set('nav_poll', !$prefs->isLocked('nav_poll') && !$prefs->getValue('nav_poll_all'));
$a_template->set('notrash', !$prefs->getValue('use_trash'));
$a_template->set('file_upload', $_SESSION['imp']['file_upload']);
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

    $tmp2 = IMP::displayFolder($val->value, true);
    if ($tmp != $tmp2) {
        $fullNames[$key] = $tmp2;
    }
}

/* Check to see if user wants new mail notification */
if (!empty($imaptree->recent)) {
    /* Open the mailbox R/W so we ensure the 'recent' flags are cleared from
     * the current mailbox. */
    foreach ($imaptree->recent as $mbox => $nm) {
        $injector->getInstance('IMP_Imap')->getOb()->openMailbox($mbox, Horde_Imap_Client::OPEN_READWRITE);
    }

    IMP::newmailAlerts($imaptree->recent);
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
