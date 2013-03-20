<?php
/**
 * Gollem main file manager script.
 *
 * Copyright 1999-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did notcan receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Max Kalika <max@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('gollem');

$backkey = $session->get('gollem', 'backend_key');
$clipboard = $session->get('gollem', 'clipboard', Horde_Session::TYPE_ARRAY);
$vars = Horde_Variables::getDefaultVariables();

/* Set directory. */
try {
    Gollem::changeDir();
} catch (Gollem_Exception $e) {
    $notification->push($e);
}
$old_dir = Gollem::$backend['dir'];

/* Get permissions. */
$delete_perms = Gollem::checkPermissions('backend', Horde_Perms::DELETE) &&
    Gollem::checkPermissions('directory', Horde_Perms::DELETE, Gollem::$backend['dir']);
$edit_perms = Gollem::checkPermissions('backend', Horde_Perms::EDIT) &&
    Gollem::checkPermissions('directory', Horde_Perms::EDIT, Gollem::$backend['dir']);
$read_perms = Gollem::checkPermissions('backend', Horde_Perms::READ) &&
    Gollem::checkPermissions('directory', Horde_Perms::READ, Gollem::$backend['dir']);

/* Get VFS object. */
$gollem_vfs = $injector->getInstance('Gollem_Vfs');

/* Run through the action handlers. */
switch ($vars->actionID) {
case 'create_folder':
    if ($edit_perms && isset($vars->new_folder)) {
        try {
            Gollem::createFolder($old_dir, $vars->new_folder);
            $notification->push(_("New folder created: ") . $vars->new_folder, 'horde.success');
        } catch (Gollem_Exception $e) {
            $notification->push($e, 'horde.error');
        }
    }
    break;

case 'rename_items':
    if ($edit_perms && isset($vars->new_names) && isset($vars->old_names)) {
        $new = explode('|', $vars->new_names);
        $old = explode('|', $vars->old_names);
        if (count($new) == count($old)) {
            for ($i = 0, $iMax = count($new); $i < $iMax; ++$i) {
                try {
                    Gollem::renameItem($old_dir, $old[$i], $old_dir, $new[$i]);
                    $notification->push(sprintf(_("\"%s\" renamed to \"%s\""), $old[$i], $new[$i]), 'horde.success');
                } catch (Gollem_Exception $e) {
                    $notification->push($e, 'horde.error');
                }
            }
            Gollem::expireCache($old_dir);
        }
    }
    break;

case 'chmod_modify':
case 'delete_items':
    if ($delete_perms && is_array($vars->items) && count($vars->items)) {
        foreach ($vars->items as $item) {
            if (($vars->actionID == 'chmod_modify') && $vars->chmod) {
                try {
                    Gollem::changePermissions(Gollem::$backend['dir'], $item, $vars->chmod);
                    Gollem::expireCache($old_dir);
                    $notification->push(_("Chmod done: ") . $item, 'horde.success');
                } catch (Gollem_Exception $e) {
                    $notification->push(sprintf(_("Cannot chmod %s: %s"), $item, $e->getMessage()), 'horde.error');
                }
            } elseif ($vars->actionID == 'delete_items') {
                if ($gollem_vfs->isFolder($old_dir, $item)) {
                    try {
                        Gollem::deleteFolder($old_dir, $item);
                        Gollem::expireCache($old_dir);
                        $notification->push(_("Folder removed: ") . $item, 'horde.success');
                    } catch (Gollem_Exception $e) {
                        $notification->push(sprintf(_("Unable to delete folder %s: %s"), $item, $e->getMessage()), 'horde.error');
                    }
                } else {
                    try {
                        Gollem::deleteFile($old_dir, $item);
                        Gollem::expireCache($old_dir);
                        $notification->push(_("File deleted: ") . $item, 'horde.success');
                    } catch (Gollem_Exception $e) {
                        $notification->push(sprintf(_("Unable to delete file %s: %s"), $item, $e->getMessage()), 'horde.error');
                    }
                }
            }
        }
    }
    break;

case 'upload_file':
    if ($edit_perms) {
        for ($i = 1, $l = count($_FILES); $i <= $l; ++$i) {
            $val = 'file_upload_' . $i;
            if (isset($_FILES[$val]) && ($_FILES[$val]['error'] != 4)) {
                try {
                    $browser->wasFileUploaded($val);
                    $filename = Horde_Util::dispelMagicQuotes($_FILES[$val]['name']);

                    Gollem::writeFile($old_dir, $filename, $_FILES[$val]['tmp_name']);
                    Gollem::expireCache($old_dir);
                    $notification->push(sprintf(_("File received: %s"), $filename), 'horde.success');
                } catch (Gollem_Exception $e) {
                    $notification->push($e, 'horde.error');
                }
            }
        }
    }
    break;

case 'copy_items':
case 'cut_items':
    if ($edit_perms) {
        $action = ($vars->actionID == 'copy_items') ? 'copy' : 'cut';

        if (is_array($vars->items) && count($vars->items)) {
            foreach ($vars->items as $item) {
                $file = array(
                    'action' => $action,
                    'backend' => $backkey,
                    'display' => Gollem::getDisplayPath($old_dir . '/' . $item),
                    'name' => $item,
                    'path' => $old_dir
                );
                $clipboard[] = $file;
                $session->set('gollem', 'clipboard', $clipboard);
                if ($action == 'copy') {
                    $notification->push(sprintf(_("Item copied to clipboard: %s"), $item), 'horde.success');
                } else {
                    $notification->push(sprintf(_("Item cut to clipboard: %s"), $item), 'horde.success');
                }
            }
        } elseif ($action == 'copy') {
            $notification->push(_("Cannot copy items onto clipboard."), 'horde.error');
        } else {
            $notification->push(_("Cannot cut items onto clipboard."), 'horde.error');
        }
    }
    break;

case 'clear_items':
case 'paste_items':
    if ($edit_perms && is_array($vars->items) && count($vars->items)) {
        foreach ($vars->items as $val) {
            if (isset($clipboard[$val])) {
                $file = $clipboard[$val];
                if ($vars->actionID == 'paste_items') {
                    try {
                        if ($file['action'] == 'cut') {
                            Gollem::moveFile($file['backend'], $file['path'], $file['name'], $backkey, $old_dir);
                        } else {
                            Gollem::copyFile($file['backend'], $file['path'], $file['name'], $backkey, $old_dir);
                        }

                        Gollem::expireCache($old_dir);
                        if ($file['action'] == 'cut') {
                            Gollem::expireCache($file['path']);
                        }
                        $notification->push(sprintf(_("%s was successfully pasted."), $file['name'], $old_dir), 'horde.success');
                    } catch (Horde_Vfs_Exception $e) {
                        $notification->push(sprintf(_("Cannot paste \"%s\" (file cleared from clipboard): %s"), $file['name'], $e->getMessage()), 'horde.error');
                    }
                }
                unset($clipboard[$val]);
            }
        }
        $session->set('gollem', 'clipboard', array_values($clipboard));
    }
    break;

case 'change_sortby':
    if (isset($vars->sortby)) {
        $prefs->setValue('sortby', $vars->sortby);
    }
    break;

case 'change_sortdir':
    if (isset($vars->sortdir)) {
        $prefs->setValue('sortdir', $vars->sortdir);
    }
    break;
}

/* First loop through getting folder lists, setting the directory,
 * etc., to make sure we can catch any errors. */
try {
    $list = Gollem::listFolder(Gollem::$backend['dir']);
} catch (Horde_Exception $e) {
    /* If this is a user's home directory, try autocreating it. */
    if (Gollem::$backend['dir'] == Gollem::$backend['home']) {
        try {
            Gollem::createFolder('', Gollem::$backend['dir']);
            try {
                $list = Gollem::listFolder(Gollem::$backend['dir']);
            } catch (Horde_Exception $e) {
                /* If that didn't work, fall back to the parent or the home
                 * directory. */
                $notification->push(sprintf(_("Permission denied to folder \"%s\": %s"), Gollem::$backend['dir'], $e->getMessage()), 'horde.error');

                $loc = strrpos(Gollem::$backend['dir'], '/');
                Gollem::setDir(($loc !== false) ? substr(Gollem::$backend['dir'], 0, $loc) : Gollem::$backend['home']);
                $list = Gollem::listFolder(Gollem::$backend['dir']);
            }
        } catch (Gollem_Exception $e) {
            $notification->push(sprintf(_("Cannot create home directory: %s"), $e->getMessage()), 'horde.error');
        }
    }
}

$numitem = count($list);
$title = Gollem::$backend['name'];

/* Commonly used URLs. */
$view_url = Horde::url('view.php');
$edit_url = Horde::url('edit.php');
$manager_url = Horde::url('manager.php');

$refresh_url = Horde::selfUrl(true, true);

/* Init some form vars. */
if ($session->get('gollem', 'filter') != $vars->searchfield) {
    if (strlen($vars->searchfield)) {
        $refresh_url->add('searchfield', $vars->searchfield);
    } else {
        $refresh_url->remove('searchfield');
    }
    $page = 0;
} else {
    $page = $vars->get('page', 0);
}
$session->set('gollem', 'filter', strval($vars->searchfield));

/* Get the list of copy/cut files in this directory. */
$clipboard_files = array();
foreach ($clipboard as $val) {
    if (($backkey == $val['backend']) &&
        ($val['path'] == Gollem::$backend['dir'])) {
        $clipboard_files[$val['name']] = 1;
    }
}

/* Read the columns to display from the preferences. */
$columns = Gollem::getColumns($backkey);

/* Prepare the template. */
$template = $injector->createInstance('Horde_View');

$attrib = $gollem_vfs->getModifiablePermissions();
foreach (array('owner', 'group', 'all') as $val) {
    foreach (array('read', 'write', 'execute') as $val2) {
        if (isset($attrib[$val][$val2])) {
            $template->{$val . '_' . $val2} = !$attrib[$val][$val2];
        }
    }
}

$all_columns = array('type', 'name', 'share', 'edit', 'download', 'modified', 'size', 'permission', 'owner', 'group');
foreach ($all_columns as $column) {
    $template->{'columns_' . $column} = in_array($column, $columns);
}

$template->action = $refresh_url;
$template->forminput = Horde_Util::formInput();
$template->dir = Gollem::$backend['dir'];
$template->navlink = Gollem::directoryNavLink(Gollem::$backend['dir'], $manager_url);
$template->refresh = Horde::widget(array(
    'url' => $refresh_url,
    'title' => _("Refresh"),
    'id' => 'gollem-refresh'
));

$template->hasclipboard = $edit_perms;

$shares_enabled = !empty(Gollem::$backend['shares']) &&
    strpos(Gollem::$backend['dir'], Gollem::$backend['home']) === 0;
if ($shares_enabled) {
    $shares = $injector->getInstance('Gollem_Shares');
    $perms_url_base = Horde::url('share.php', true)->add('app', 'gollem');
    $share_name = $backkey . '|' . Gollem::$backend['dir'];
    $template->share_folder = Horde::widget(array(
        'url' => $perms_url_base->add('share', $share_name),
        'title' => _("Share Folder"),
        'target' => '_blank',
        'class' => 'gollem-sharefolder',
        'onclick' => Horde::popupJs(
            $perms_url_base,
            array('params' => array('share' => $share_name),
                  'urlencode' => true)) . 'return false;'
    ));
}

if ($edit_perms) {
    $template->perms_edit = true;
    $template->upload_file = _("Upload File(s)");
    $template->upload_identifier = session_id();
    $template->upload_help = Horde_Help::link('gollem', 'file-upload');
    $template->perms_chmod = in_array('permission', $columns);
    $injector->getInstance('Horde_View_Sidebar')->addNewButton(
        _("Create Folder"),
        Horde::url('#'),
        array('id' => 'gollem-createfolder')
    );
} else {
    $template->perms_edit = false;
    $template->perms_chmod = false;
}

if ($read_perms) {
    $template->change_folder = Horde::widget(array(
        'url' => Horde::url('#'),
        'title' => _("Change Folder"),
        'id' => 'gollem-changefolder'
    ));
}

if ($numitem) {
    $template->list_count = true;
    $template->perms_delete = $delete_perms;
    $topbar = $injector->getInstance('Horde_View_Topbar');
    $topbar->search = true;
    $topbar->searchAction = $refresh_url;
    $topbar->searchLabel = $session->get('gollem', 'filter') ?: _("Filter");
    $topbar->searchIcon = Horde_Themes::img('filter-topbar.png');
} else {
    $template->list_count = false;
}

$icon_cache = array();
$total = 0;

if (is_array($list) && $numitem && $read_perms) {
    $entry = array();
    $page_caption = '';

    $template->empty_dir = false;

    /* Set list min/max values */
    $perpage = $prefs->getValue('perpage');
    $min = $page * $perpage;
    while ($min > $numitem) {
        --$page;
        $min = $page * $perpage;
    }
    $max = $min + $perpage;

    foreach ($list as $key => $val) {
        /* Check if a filter is not empty and filter matches filename. */
        if (strlen($vars->searchfield) &&
            !preg_match('/' . preg_quote($vars->searchfield, '/') . '/', $val['name'])) {
            continue;
        }

        /* Continue if item not in min/max range. */
        if (($total++ < $min) || ($total > $max)) {
            continue;
        }

        $item = array(
            'date' => htmlspecialchars(strftime($prefs->getValue('date_format_mini'), $val['date'])),
            'dl' => false,
            'edit' => false,
            'group' => empty($val['group']) ? '-' : htmlspecialchars($val['group']),
            'name' => htmlspecialchars($val['name']),
            'on_clipboard' => false,
            'owner' => empty($val['owner']) ? '-' : htmlspecialchars($val['owner']),
            'perms' => empty($val['perms']) ? '-' : htmlspecialchars($val['perms']),
            'size' => ($val['type'] == '**dir') ? '-' : number_format($val['size'], 0, '.', ','),
            'type' => htmlspecialchars($val['type'])
        );

        $name = str_replace(' ', '&nbsp;', $item['name']);

        /* Is this file on the clipboard? */
        if (isset($clipboard_files[$val['name']])) {
            $item['on_clipboard'] = true;
        }

        /* Determine graphic to use. */
        if (!empty($val['link'])) {
            $item['graphic'] = '<span class="iconImg gollem-symlink"></span>';
        } elseif ($val['type'] == '**dir') {
            $item['graphic'] = '<span class="iconImg gollem-folder"></span>';
        } else {
            if (empty($icon_cache[$val['type']])) {
                $icon_cache[$val['type']] = Horde::img($injector->getInstance('Horde_Core_Factory_MimeViewer')->getIcon(Horde_Mime_Magic::extToMime($val['type'])), '', '', '');
            }
            $item['graphic'] = $icon_cache[$val['type']];
        }

        /* Create proper link. */
        switch ($val['type']) {
        case '**dir':
            $subdir = Gollem::subdirectory(Gollem::$backend['dir'], $val['name']);
            if (!Gollem::checkPermissions('directory', Horde_Perms::SHOW, $subdir)) {
                continue 2;
            }
            $item['link'] = $manager_url->copy()->add('dir', $subdir)->link()
                . $name . '</a>';
            if ($shares_enabled) {
                $share = $backkey . '|' . $subdir;
                $item['share'] = $perms_url_base->add('share', $share)->link(array('title' => $shares->exists($share) ? _("Shared Folder") : _("Share Folder"), 'target' => '_blank', 'onclick' => Horde::popupJs($perms_url_base, array('params' => array('share' => $share), 'urlencode' => true)) . 'return false;'));
                $item['share_disabled'] = !$shares->exists($share);
            }
            break;

        case '**broken':
            $item['link'] = $name;
            break;

        case '**sym':
            if ($val['linktype'] === '**dir') {
                if (substr($val['link'], 0, 1) == '/') {
                    $parts = explode('/', $val['link']);
                    $name = array_pop($parts);
                    $dir = implode('/', $parts);
                } else {
                    $name = $val['link'];
                    $dir = Gollem::$backend['dir'];
                }

                $url = $manager_url->copy()->add('dir', Gollem::subdirectory($dir, $name));
                $item['link'] = $item['name'] . ' -> ' . $url->link() . $val['link'] . '</a>';
            } else {
                $item['link'] = $item['name'] . ' -> ' . $val['link'];
            }
            break;

        default:
            $mime_type = Horde_Mime_Magic::extToMime($val['type']);

            // Edit link if possible.
            if (strpos($mime_type, 'text/') === 0) {
                $url = $edit_url->copy()->add(array(
                    'actionID' => 'edit_file',
                    'type' => $val['type'],
                    'file' => $val['name'],
                    'dir' => Gollem::$backend['dir'],
                    'driver' => Gollem::$backend['driver']
                ));
                $item['edit'] = Horde::link('#', '', '', '_blank', Horde::popupJs($url));
            }

            // We can always download files.
            $item['dl'] = $registry->downloadUrl($val['name'], array('dir' => Gollem::$backend['dir'], 'backend' => $GLOBALS['session']->get('gollem', 'backend_key')))->link(array('title' => sprintf(_("Download %s"), $val['name'])));

            // Try a view link.
            $url = $view_url->copy()->add(array(
                'type' => $val['type'],
                'file' => $val['name'],
                'dir' => Gollem::$backend['dir'],
                'driver' => Gollem::$backend['driver']
            ));
            $item['link'] = Horde::link('#', '', '', '_blank', Horde::popupJs($url)) . $name . '</a>';
            break;
        }

        $entry[] = $item;
    }

    /* Set up the variables needed for the header row. */
    $sortby = $prefs->getValue('sortby');
    $sortdir = $prefs->getValue('sortdir');

    if ($total) {
        // Set start/end items (according to current page)
        $start = ($page * $perpage) + 1;
        $end = min($total, $start + $perpage - 1);

        $vars->set('page', $page);
        $pager = new Horde_Core_Ui_Pager('page', $vars, array(
            'num' => $total,
            'url' => $refresh_url,
            'page_count' => 10,
            'perpage' => $perpage
        ));
        $page_caption = $pager->render();
    }

    $headers = array();
    foreach ($columns as $head) {
        $hdr = array('class' => '');
        $sort = null;

        switch ($head) {
        case 'type':
            $hdr['width'] = '3%';
            $hdr['label'] = _("Type");
            $hdr['align'] = 'right';
            $hdr['id'] = 's' . Gollem::SORT_TYPE;
            $sort = Gollem::SORT_TYPE;
            break;

        case 'name':
            $hdr['width'] = '57%';
            $hdr['label'] = _("Name");
            $hdr['align'] = 'left';
            $hdr['id'] = 's' . Gollem::SORT_NAME;
            $sort = Gollem::SORT_NAME;
            break;

        case 'share':
            $hdr['width'] = '1%';
            $hdr['label'] = '&nbsp;';
            $hdr['align'] = 'center';
            break;

        case 'edit':
            $hdr['width'] = '1%';
            $hdr['label'] = '&nbsp;';
            $hdr['align'] = 'center';
            break;

        case 'download':
            $hdr['width'] = '1%';
            $hdr['label'] = '&nbsp;';
            $hdr['align'] = 'center';
            break;

        case 'modified':
            $hdr['width'] = '7%';
            $hdr['label'] = _("Modified");
            $hdr['align'] = 'left';
            $hdr['id'] = 's' . Gollem::SORT_DATE;
            $sort = Gollem::SORT_DATE;
            break;

        case 'size':
            $hdr['width'] = '7%';
            $hdr['label'] = _("Size");
            $hdr['align'] = 'right';
            $hdr['id'] = 's' . Gollem::SORT_SIZE;
            $sort = Gollem::SORT_SIZE;
            break;

        case 'permission':
            $hdr['width'] = '7%';
            $hdr['label'] = _("Permission");
            $hdr['align'] = 'right';
            break;

        case 'owner':
            $hdr['width'] = '7%';
            $hdr['label'] = _("Owner");
            $hdr['align'] = 'right';
            break;

        case 'group':
            $hdr['width'] = '7%';
            $hdr['label'] = _("Group");
            $hdr['align'] = 'right';
            break;
        }

        if ($sort !== null) {
            if ($sortby == $sort) {
                $hdr['class'] = ($sortdir ? 'sortup' : 'sortdown');
                $params = array('actionID' => 'change_sortdir', 'sortdir' => 1 - $sortdir);
            } else {
                $params = array('actionID' => 'change_sortby', 'sortby' => $sort);
            }
            $hdr['label'] = '<a href="' . Horde::selfUrl()->add($params) . '" class="sortlink">' . htmlspecialchars($hdr['label']) . '</a>';
        }

        $headers[] = $hdr;
    }

    /* Set up the template tags. */
    $template->headers = $headers;
    $template->entries = $entry;
    $template->page_caption = $page_caption;
    $template->checkall = Horde::getAccessKeyAndTitle(_("Check _All/None"));
} else {
    $template->empty_dir = true;
}
$template->itemcount = sprintf(ngettext(_("%d item"), _("%d items"), $total), $total);

$page_output->addScriptFile('manager.js');
$page_output->addScriptPackage('Dialog');
$page_output->addInlineJsVars(array(
    'var GollemVar' => array(
        'actionUrl' => strval(Horde::url('manager.php')),
        'empty_input' => intval($GLOBALS['browser']->hasQuirk('empty_file_input_value')),
        'warn_recursive' => intval($prefs->getValue('recursive_deletes') == 'warn')
    ),
    'var GollemText' => array(
        'change_directory' => _("Change Folder"),
        'create_folder' => _("Create Folder"),
        'delete_confirm_1' => _("The following items will be permanently deleted:"),
        'delete_confirm_2' => _("Are you sure?"),
        'delete_recurs_1' => _("The following item(s) are folders:"),
        'delete_recurs_2' => _("Are you sure you wish to continue?"),
        'file' => _("File"),
        'permissions' => _("Permissions"),
        'rename' => _("Rename"),
        'select_item' => _("Please select an item before this action."),
        'specify_upload' => _("Please specify at least one file to upload."),
    )
));

$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
echo $template->render('manager');
$page_output->footer();
