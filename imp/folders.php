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
    Horde::applicationUrl('mailbox.php', true)->redirect();
}

/* Decide whether or not to show all the unsubscribed folders */
$subscribe = $prefs->getValue('subscribe');
$showAll = (!$subscribe || $_SESSION['imp']['showunsub']);

$charset = $registry->getCharset();

/* Get the base URL for this page. */
$folders_url = Horde::selfUrl();

/* This JS define is required by all folder pages. */
Horde::addInlineScript(array(
    'ImpFolders.folders_url = ' . Horde_Serialize::serialize(strval($folders_url), Horde_Serialize::JSON, $charset)
));

/* Initialize the IMP_Folder object. */
$imp_folder = $injector->getInstance('IMP_Folder');

/* Initialize the IMP_Imap_Tree object. */
$imaptree = $injector->getInstance('IMP_Imap_Tree');

/* $folder_list is already encoded in UTF7-IMAP. */
$folder_list = Horde_Util::getFormData('folder_list', array());

/* META refresh time (might be altered by actionID). */
$refresh_time = $prefs->getValue('refresh_time');

/* Run through the action handlers. */
$actionID = Horde_Util::getFormData('actionID');
if ($actionID) {
    try {
        Horde::checkRequestToken('imp.folders', Horde_Util::getFormData('folders_token'));
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $actionID = null;
    }
}

switch ($actionID) {
case 'collapse_folder':
case 'expand_folder':
    $folder = Horde_Util::getFormData('folder');
    if (!empty($folder)) {
        ($actionID == 'expand_folder') ? $imaptree->expand($folder) : $imaptree->collapse($folder);
    }
    break;

case 'expand_all_folders':
    $imaptree->expandAll();
    break;

case 'collapse_all_folders':
    $imaptree->collapseAll();
    break;

case 'rebuild_tree':
    $imp_folder->clearFlistCache();
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

case 'delete_search_query':
    $queryid = Horde_Util::getFormData('queryid');
    if (!empty($queryid)) {
        $imp_search = $injector->getInstance('IMP_Search');
        $notification->push(sprintf(_("Deleted Virtual Folder \"%s\"."), $imp_search->getLabel($queryid)), 'horde.success');
        $imp_search->deleteSearchQuery($queryid);
    }
    break;

case 'download_folder':
case 'download_folder_zip':
    if (!empty($folder_list)) {
        $mbox = $imp_folder->generateMbox($folder_list);
        if ($actionID == 'download_folder') {
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
    $import_folder = Horde_Util::getFormData('import_folder');
    if (!empty($import_folder)) {
        try {
            $browser->wasFileUploaded('mbox_upload', _("mailbox file"));
            $res = $imp_folder->importMbox(Horde_String::convertCharset($import_folder, $charset, 'UTF7-IMAP'), $_FILES['mbox_upload']['tmp_name']);
            $mbox_name = basename(Horde_Util::dispelMagicQuotes($_FILES['mbox_upload']['name']));
            if ($res === false) {
                $notification->push(sprintf(_("There was an error importing %s."), $mbox_name), 'horde.error');
            } else {
                $notification->push(sprintf(_("Imported %d messages from %s."), $res, $mbox_name), 'horde.success');
            }
        } catch (Horde_Browser_Exception $e) {
            $notification->push($e);
        }
        $actionID = null;
    } else {
        $refresh_time = null;
    }
    break;

case 'create_folder':
    $new_mailbox = Horde_Util::getFormData('new_mailbox');
    if (!empty($new_mailbox)) {
        try {
            $new_mailbox = $imaptree->createMailboxName(array_shift($folder_list), Horde_String::convertCharset($new_mailbox, $charset, 'UTF7-IMAP'));
            $imp_folder->create($new_mailbox, $subscribe);
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
    }
    break;

case 'rename_folder':
    // $old_names already in UTF7-IMAP
    $old_names = array_map('trim', explode("\n", Horde_Util::getFormData('old_names')));
    $new_names = array_map('trim', explode("\n", Horde_Util::getFormData('new_names')));

    $iMax = count($new_names);
    if (!empty($new_names) &&
        !empty($old_names) &&
        ($iMax == count($old_names))) {
        for ($i = 0; $i < $iMax; ++$i) {
            $old_ns = $imp_imap->getNamespace($old_names[$i]);
            $new = trim($new_names[$i], $old_ns['delimiter']);

            /* If this is a personal namespace, then anything goes as far as
             * the input. Just append the personal namespace to it. For
             * others, add the  */
            if (($old_ns['type'] == 'personal') ||
                ($old_ns['name'] &&
                 (stripos($new_names[$i], $old_ns['name']) !== 0))) {
                $new = $old_ns['name'] . $new;
            }

            $imp_folder->rename($old_names[$i], Horde_String::convertCharset($new, $charset, 'UTF7-IMAP'));
        }
    }
    break;

case 'subscribe_folder':
case 'unsubscribe_folder':
    if (!empty($folder_list)) {
        if ($actionID == 'subscribe_folder') {
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
        $injector->getInstance('IMP_Message')->flagAllInMailbox(array('seen'), $folder_list, ($actionID == 'mark_folder_seen'));
    }
    break;

case 'delete_folder_confirm':
case 'folders_empty_mailbox_confirm':
    if (!empty($folder_list)) {
        $loop = array();
        $rowct = 0;
        foreach ($folder_list as $val) {
            if (($actionID == 'delete_folder_confirm') &&
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
                'class' => (++$rowct % 2) ? 'item0' : 'item1',
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
        IMP::prepareMenu();
        require IMP_TEMPLATES . '/common-header.inc';
        IMP::menu();

        $template = $injector->createInstance('Horde_Template');
        $template->setOption('gettext', true);
        $template->set('delete', ($actionID == 'delete_folder_confirm'));
        $template->set('empty', ($actionID == 'folders_empty_mailbox_confirm'));
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
        IMP::prepareMenu();
        require IMP_TEMPLATES . '/common-header.inc';
        IMP::menu();
        IMP::status();
        IMP::quota();

        $loop = array();
        $rowct = $sum = 0;

        $imp_message = $injector->getInstance('IMP_Message');

        foreach ($folder_list as $val) {
            $size = $imp_message->sizeMailbox($val, false);
            $data = array(
                'class' => (++$rowct % 2) ? 'item0' : 'item1',
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

if ($_SESSION['imp']['file_upload'] && ($actionID == 'import_mbox')) {
    $title = _("Folder Navigator");
    IMP::prepareMenu();
    require IMP_TEMPLATES . '/common-header.inc';
    IMP::menu();
    IMP::status();
    IMP::quota();

    /* Prepare import template. */
    $i_template = $injector->createInstance('Horde_Template');
    $i_template->setOption('gettext', true);
    $i_template->set('folders_url', $folders_url_ob);
    $i_template->set('import_folder', $folder_list[0]);
    $i_template->set('folder_name', htmlspecialchars(Horde_String::convertCharset($folder_list[0], 'UTF7-IMAP'), ENT_COMPAT, $charset));
    $i_template->set('folders_token', $folders_token);
    echo $i_template->fetch(IMP_TEMPLATES . '/imp/folders/import.html');
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

/* Build the folder tree. */
list($raw_rows, $newmsgs) = $imaptree->build();

/* Build the list of display names. */
reset($raw_rows);
$displayNames = $fullNames = array();
while (list($k, $r) = each($raw_rows)) {
    $displayNames[] = $r['display'];

    $tmp = IMP::displayFolder($r['value'], true);
    if ($tmp != $r['display']) {
        $fullNames[$k] = $tmp;
    }
}

Horde::addInlineScript(array(
    'ImpFolders.displayNames = ' . Horde_Serialize::serialize($displayNames, Horde_Serialize::JSON, $charset),
    'ImpFolders.fullNames = ' . Horde_Serialize::serialize($fullNames, Horde_Serialize::JSON, $charset)
));

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
$a_template->set('help', Horde_Help::link('imp', 'folder-options'));
$a_template->set('expand_all', Horde::widget($folders_url_ob->copy()->add(array('actionID' => 'expand_all_folders', 'folders_token' => $folders_token)), _("Expand All Folders"), 'widget', '', '', _("Expand All"), true));
$a_template->set('collapse_all', Horde::widget($folders_url_ob->copy()->add(array('actionID' => 'collapse_all_folders', 'folders_token' => $folders_token)), _("Collapse All Folders"), 'widget', '', '', _("Collapse All"), true));

/* Check to see if user wants new mail notification */
if (!empty($newmsgs)) {
    /* Open the mailbox R/W so we ensure the 'recent' flags are cleared from
     * the current mailbox. */
    foreach ($newmsgs as $mbox => $nm) {
        $injector->getInstance('IMP_Imap')->getOb()->openMailbox($mbox, Horde_Imap_Client::OPEN_READWRITE);
    }

    IMP::newmailAlerts($newmsgs);
}

/* Get the tree images. */
$imp_ui_folder = new IMP_Ui_Folder();
$tree_imgs = $imp_ui_folder->getTreeImages($raw_rows, array('expand_url' => $folders_url_ob));

/* Add some further information to the $raw_rows array. */
$rows = array();
$name_url = Horde::applicationUrl('mailbox.php')->add('no_newmail_popup', 1);
$rowct = 0;

foreach ($raw_rows as $key => $val) {
    $val['nocheckbox'] = !empty($val['vfolder']);
    if (!empty($val['vfolder']) && $val['editvfolder']) {
        $imp_search = $injector->getInstance('IMP_Search');
        $val['delvfolder'] = $imp_search->deleteUrl($val['value'])->link(array('title' => _("Delete Virtual Folder"))) . _("Delete") . '</a>';
        $val['editvfolder'] = $imp_search->editUrl($val['value'])->link(array('title' => _("Edit Virtual Folder"))) . _("Edit") . '</a>';
    }

    $val['cname'] = (++$rowct % 2) ? 'item0' : 'item1';

    /* Highlight line differently if folder/mailbox is unsubscribed. */
    if ($showAll &&
        $subscribe &&
        !$val['container'] &&
        !$imaptree->isSubscribed($val['base_elt'])) {
        $val['cname'] .= ' folderunsub';
    }

    if (!$val['container']) {
        if (!empty($val['unseen'])) {
            $val['name'] = '<strong>' . $val['name'] . '</strong>';
        }
        $val['name'] = $name_url->copy()->add('mailbox', $val['value'])->link(array('title' => $val['vfolder'] ? $val['base_elt']['l'] : $val['display'])) . $val['name'] . '</a>';
    }

    $val['line'] = $tree_imgs[$key];

    $rows[] = $val;
}

/* Render the rows now. */
$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);
$template->set('rows', $rows);

$title = _("Folder Navigator");
IMP::prepareMenu();
Horde::metaRefresh($refresh_time, Horde::applicationUrl('folders.php', true));
require IMP_TEMPLATES . '/common-header.inc';
IMP::menu();
IMP::status();
IMP::quota();

echo $head_template->fetch(IMP_TEMPLATES . '/imp/folders/head.html');
echo $a_template->fetch(IMP_TEMPLATES . '/imp/folders/actions.html');
echo $template->fetch(IMP_TEMPLATES . '/imp/folders/folders.html');
if (count($rows) > 10) {
    $a_template->set('id', 1);
    echo $a_template->fetch(IMP_TEMPLATES . '/imp/folders/actions.html');
}

/* No need for extra template - close out the tags here. */
echo '</form></div>';

require $registry->get('templates', 'horde') . '/common-footer.inc';
