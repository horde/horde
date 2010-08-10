<?php
/**
 * Gollem main file manager script.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did notcan receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Max Kalika <max@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Gollem
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('gollem');

$actionID = Horde_Util::getFormData('actionID');
$backkey = $_SESSION['gollem']['backend_key'];
$old_dir = Gollem::getDir();

/* Get permissions. */
$delete_perms = Gollem::checkPermissions('backend', Horde_Perms::DELETE);
$edit_perms = Gollem::checkPermissions('backend', Horde_Perms::EDIT);
$read_perms = Gollem::checkPermissions('backend', Horde_Perms::READ);

/* Set directory. */
if (is_a($result = Gollem::changeDir(), 'PEAR_Error')) {
    $notification->push($result);
}

/* Run through the action handlers. */
switch ($actionID) {
case 'create_folder':
    if ($edit_perms) {
        if ($new_folder = Horde_Util::getPost('new_folder')) {
            $result = Gollem::createFolder($old_dir, $new_folder);
            if (is_a($result, 'PEAR_Error')) {
                $notification->push($result->getMessage(), 'horde.error');
            } else {
                $notification->push(_("New folder created: ") . $new_folder, 'horde.success');
            }
        }
    }
    break;

case 'rename_items':
    if ($edit_perms) {
        $new = explode('|', Horde_Util::getPost('new_names'));
        $old = explode('|', Horde_Util::getPost('old_names'));
        if (!empty($new) && !empty($old) && (count($new) == count($old))) {
            for ($i = 0, $iMax = count($new); $i < $iMax; ++$i) {
                $result = Gollem::renameItem($old_dir, $old[$i], $old_dir, $new[$i]);
                if (is_a($result, 'PEAR_Error')) {
                    $notification->push($result->getMessage(), 'horde.error');
                } else {
                    $notification->push(sprintf(_("\"%s\" renamed to \"%s\""), $old[$i], $new[$i]), 'horde.success');
                }
            }
            Gollem::expireCache($old_dir);
        } else {
            $notification->push(_("Incorrect number of items."), 'horde.error');
        }
    }
    break;

case 'chmod_modify':
case 'delete_items':
    if ($delete_perms) {
        $items = Horde_Util::getPost('items');
        if (is_array($items) && count($items)) {
            $chmod = Horde_Util::getPost('chmod');
            foreach ($items as $item) {
                if (($actionID == 'chmod_modify') && $chmod) {
                    if (!is_a(Gollem::changePermissions(Gollem::getDir(), $item, $chmod), 'PEAR_Error')) {
                        Gollem::expireCache($old_dir);
                        $notification->push(_("Chmod done: ") . $item, 'horde.success');
                    } else {
                        $notification->push(sprintf(_("Cannot chmod %s: %s"), $item, $result->getMessage()), 'horde.error');
                    }
                } elseif ($actionID == 'delete_items') {
                    if ($GLOBALS['gollem_vfs']->isFolder($old_dir, $item)) {
                        $result = Gollem::deleteFolder($old_dir, $item);
                        if (is_a($result, 'PEAR_Error')) {
                            $notification->push(sprintf(_("Unable to delete folder %s: %s"), $item, $result->getMessage()), 'horde.error');
                        } else {
                            Gollem::expireCache($old_dir);
                            $notification->push(_("Folder removed: ") . $item, 'horde.success');
                        }
                    } else {
                        $result = Gollem::deleteFile($old_dir, $item);
                        if (is_a($result, 'PEAR_Error')) {
                            $notification->push(sprintf(_("Unable to delete file %s: %s"), $item, $result->getMessage()), 'horde.error');
                        } else {
                            Gollem::expireCache($old_dir);
                            $notification->push(_("File deleted: ") . $item, 'horde.success');
                        }
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
                    $GLOBALS['browser']->wasFileUploaded($val);
                    $filename = Horde_Util::dispelMagicQuotes($_FILES[$val]['name']);
                    $res = Gollem::writeFile($old_dir, $filename, $_FILES[$val]['tmp_name']);
                    if (is_a($res, 'PEAR_Error')) {
                        $notification->push($res, 'horde.error');
                    } else {
                        Gollem::expireCache($old_dir);
                        $notification->push(sprintf(_("File received: %s"), $filename), 'horde.success');
                    }
                } catch (Horde_Browser_Exception $e) {
                    $notification->push($e, 'horde.error');
                }
            }
        }
    }
    break;

case 'copy_items':
case 'cut_items':
    if ($edit_perms && !empty($GLOBALS['gollem_be']['clipboard'])) {
        $action = ($actionID == 'copy_items') ? 'copy' : 'cut';
        $items = Horde_Util::getPost('items');

        if (is_array($items) && count($items)) {
            foreach ($items as $item) {
                $file = array(
                    'action' => $action,
                    'backend' => $backkey,
                    'display' => Gollem::getDisplayPath($old_dir . '/' . $item),
                    'name' => $item,
                    'path' => $old_dir
                );
                $_SESSION['gollem']['clipboard'][] = $file;
                if ($action == 'copy') {
                    $notification->push(sprintf(_("Item copied to clipboard: %s"), $item),'horde.success');
                } else {
                    $notification->push(sprintf(_("Item cut to clipboard: %s"), $item), 'horde.success');
                }
            }
        } else {
            if ($action == 'copy') {
                $notification->push(_("Cannot copy items onto clipboard."), 'horde.error');
            } else {
                $notification->push(_("Cannot cut items onto clipboard."), 'horde.error');
            }
        }
    }
    break;

case 'clear_items':
case 'paste_items':
    if ($edit_perms && !empty($GLOBALS['gollem_be']['clipboard'])) {
        $items = Horde_Util::getPost('items');
        if (is_array($items) && count($items)) {
            foreach ($items as $val) {
                if (isset($_SESSION['gollem']['clipboard'][$val])) {
                    $file = $_SESSION['gollem']['clipboard'][$val];
                    if ($actionID == 'paste_items') {
                        if ($file['action'] == 'cut') {
                            $res = Gollem::moveFile($file['backend'], $file['path'], $file['name'], $backkey, $old_dir);
                        } else {
                            $res = Gollem::copyFile($file['backend'], $file['path'], $file['name'], $backkey, $old_dir);
                        }
                        if (is_a($res, 'PEAR_Error')) {
                            $notification->push(sprintf(_("Cannot paste \"%s\" (file cleared from clipboard): %s"), $file['name'], $res->getMessage()), 'horde.error');
                        } else {
                            Gollem::expireCache($old_dir);
                            if ($file['action'] == 'cut') {
                                Gollem::expireCache($file['path']);
                            }
                            $notification->push(sprintf(_("%s was successfully pasted."), $file['name'], $old_dir), 'horde.success');
                        }
                    }
                    unset($_SESSION['gollem']['clipboard'][$val]);
                }
            }
            $_SESSION['gollem']['clipboard'] = array_values($_SESSION['gollem']['clipboard']);
        }
    }
    break;

case 'change_sortby':
    if (($sortby = Horde_Util::getFormData('sortby')) !== null) {
        $prefs->setValue('sortby', $sortby);
    }
    break;

case 'change_sortdir':
    if (($sortdir = Horde_Util::getFormData('sortdir')) !== null) {
        $prefs->setValue('sortdir', $sortdir);
    }
    break;
}

/* First loop through getting folder lists, setting the directory,
 * etc., to make sure we can catch any errors. */
$currdir = Gollem::getDir();

$list = Gollem::listFolder($currdir);
if (is_a($list, 'PEAR_Error')) {
    /* If this is a user's home directory, try autocreating it. */
    if ($currdir == Gollem::getHome()) {
        if (is_a($created = Gollem::createFolder('', $currdir), 'PEAR_Error')) {
            $notification->push(sprintf(_("Cannot create home directory: %s"), $created->getMessage()), 'horde.error');
        } else {
            $list = Gollem::listFolder($currdir);
        }
    }

    /* If that didn't work, fall back to the parent or the home directory. */
    if (is_a($list, 'PEAR_Error')) {
        $notification->push(sprintf(_("Permission denied to folder \"%s\": %s"), $currdir, $list->getMessage()), 'horde.error');

        $loc = strrpos($currdir, '/');
        Gollem::setDir(($loc !== false) ? substr($currdir, 0, $loc) : Gollem::getHome());
        $currdir = Gollem::getDir();
        $list = Gollem::listFolder($currdir);
    }
}

$numitem = count($list);
$title = $GLOBALS['gollem_be']['label'];

/* Image links. */
$image_dir = Horde_Themes::img(null, 'horde');
$edit_img = Horde::img('edit.png', _("Edit"), null, $image_dir);
$download_img = Horde::img('download.png', _("Download"), null, $image_dir);
$folder_img = Horde::img('folder.png', _("folder"));
$symlink_img = Horde::img('folder_symlink.png', _("symlink"));

/* Init some form vars. */
$page = Horde_Util::getFormData('page', 0);
$filter = Horde_Util::getFormData('filter', '');
if (isset($_SESSION['gollem']['filter']) &&
    $_SESSION['gollem']['filter'] != $filter) {
    $page = 0;
}
$_SESSION['gollem']['filter'] = $filter;

/* Commonly used URLs. */
$view_url = Horde::applicationUrl('view.php');
$edit_url = Horde::applicationUrl('edit.php');
$manager_url = Horde::applicationUrl('manager.php');

$refresh_params = array('page' => $page);
if ($filter) {
    $refresh_params['filter'] = $filter;
}
$refresh_url = Horde_Util::addParameter($manager_url, $refresh_params);

/* Get the list of copy/cut files in this directory. */
$clipboard_files = array();
if (!empty($GLOBALS['gollem_be']['clipboard'])) {
    foreach ($_SESSION['gollem']['clipboard'] as $val) {
        if (($backkey == $val['backend']) && ($val['path'] == $currdir)) {
            $clipboard_files[$val['name']] = 1;
        }
    }
}

/* Read the columns to display from the preferences. */
$sources = Gollem::displayColumns();
$columns = isset($sources[$backkey]) ? $sources[$backkey] : $GLOBALS['gollem_be']['attributes'];

/* Prepare the template. */
$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);

$attrib = $GLOBALS['gollem_vfs']->getModifiablePermissions();
foreach (array('owner', 'group', 'all') as $val) {
    foreach (array('read', 'write', 'execute') as $val2) {
        $template->set($val . '_' . $val2, !$attrib[$val][$val2], true);
    }
}

$all_columns = array('type', 'name', 'edit', 'download', 'modified', 'size', 'permission', 'owner', 'group');
foreach ($all_columns as $column) {
    $template->set('columns_' . $column, in_array($column, $columns), true);
}

$template->set('save', _("Save"));
$template->set('cancel', _("Cancel"));
$template->set('ok', _("OK"));
$template->set('action', $refresh_url);
$template->set('forminput', Horde_Util::formInput());
$template->set('dir', $currdir);
$template->set('navlink', Gollem::directoryNavLink($currdir, $manager_url));
$template->set('refresh', Horde::link($refresh_url, sprintf("%s %s", _("Refresh"), $GLOBALS['gollem_be']['label']), '', '', '', '', '', array('id' => 'refreshimg')) . Horde::img('reload.png', sprintf("%s %s", _("Refresh"), htmlspecialchars($GLOBALS['gollem_be']['label'])), null, $image_dir) . '</a>');

$template->set('hasclipboard', !$edit_perms || !empty($GLOBALS['gollem_be']['clipboard']), true);
if (!$template->get('hasclipboard') ||
    empty($_SESSION['gollem']['clipboard'])) {
    $template->set('clipboard', null);
} else {
    $template->set('clipboard', Horde::link(Horde_Util::addParameter(Horde::applicationUrl('clipboard.php'), 'dir', $currdir), _("View Clipboard")) . Horde::img('clipboard.png', _("View Clipboard")) . '</a>');
}

if ($edit_perms) {
    $template->set('perms_edit', true, true);
    $template->set('upload_file', _("Upload File(s)"));
    $template->set('upload_identifier', session_id());
    $template->set('upload_help', Horde_Help::link('gollem', 'file-upload'));
    $template->set('perms_chmod', in_array('permission', $columns), true);
    $template->set('create_folder', Horde::link('#', _("Create Folder"), '', '', '', '', '', array('id' => 'createfolder')) . Horde::img('folder_create.png', _("Create Folder")) . '</a>');
} else {
    $template->set('perms_edit', false, true);
    $template->set('perms_chmod', false, true);
}

if ($read_perms) {
    $template->set('change_folder', Horde::link('#', _("Change Folder"), '', '', '', '', '', array('id' => 'changefolder')) . Horde::img('folder_goto.png', _("Change Folder")) . '</a>');
}

if ($numitem) {
    $template->set('list_count', true, true);
    $template->set('perms_delete', $delete_perms);
    $template->set('actions_help', Horde_Help::link('gollem', 'file-actions'));
} else {
    $template->set('list_count', false, true);
}

$icon_cache = array();
$total = 0;

if (is_array($list) && $numitem && $read_perms) {
    $entry = array();
    $page_caption = '';

    $template->set('empty_dir', false, true);

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
        if (strlen($filter) &&
            !preg_match('/' . preg_quote($filter, '/') . '/', $val['name'])) {
            continue;
        }

        /* Continue if item not in min/max range. */
        if (($total++ < $min) || ($total > $max)) {
            continue;
        }

        $item = array(
            'date' => htmlspecialchars(strftime($prefs->getValue('date_format'), $val['date'])),
            'date_sort' => (int)$val['date'],
            'dl' => false,
            'edit' => false,
            'group' => empty($val['group']) ? '-' : htmlspecialchars($val['group']),
            'name' => htmlspecialchars($val['name']),
            'on_clipboard' => false,
            'owner' => empty($val['owner']) ? '-' : htmlspecialchars($val['owner']),
            'perms' => empty($val['perms']) ? '-' : htmlspecialchars($val['perms']),
            'size' => ($val['type'] == '**dir') ? '-' : number_format($val['size'], 0, '.', ','),
            'type' => htmlspecialchars($val['type']),
            'type_sort' => ($val['type'] == '**dir') ? '' : htmlspecialchars($val['type']),
        );

        $name = str_replace(' ', '&nbsp;', $item['name']);

        /* Is this file on the clipboard? */
        if (isset($clipboard_files[$val['name']])) {
            $item['on_clipboard'] = true;
        }

        /* Determine graphic to use. */
        if (!empty($val['link'])) {
            $item['graphic'] = $symlink_img;
        } elseif ($val['type'] == '**dir') {
            $item['graphic'] = $folder_img;
        } else {
            if (empty($icon_cache[$val['type']])) {
                $icon_cache[$val['type']] = Horde::img($injector->getInstance('Horde_Mime_Viewer')->getIcon(Horde_Mime_Magic::extToMime($val['type'])), '', '', '');
            }
            $item['graphic'] = $icon_cache[$val['type']];
        }

        /* Create proper link. */
        switch ($val['type']) {
        case '**dir':
            $url = Horde_Util::addParameter($manager_url, array('dir' => Gollem::subdirectory($currdir, $val['name'])));
            $item['link'] = Horde::link($url) . '<strong>' . $name . '</strong></a>';
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
                    $dir = $currdir;
                }

                $url = Horde_Util::addParameter($manager_url, array('dir' => Gollem::subdirectory($dir, $name)));
                $item['link'] = $item['name'] . ' -> <strong>' . Horde::link($url) . $val['link'] . '</a></strong>';
            } else {
                $item['link'] = $item['name'] . ' -> ' . $val['link'];
            }
            break;

        default:
            $mime_type = Horde_Mime_Magic::extToMime($val['type']);

            // Edit link if possible.
            if (strpos($mime_type, 'text/') === 0) {
                $url = Horde_Util::addParameter($edit_url, array('actionID' => 'edit_file', 'type' => $val['type'], 'file' => $val['name'], 'dir' => $currdir, 'driver' => $GLOBALS['gollem_be']['driver']));
                $item['edit'] = Horde::link('#', '', '', '_blank', Horde::popupJs($url) . 'return false;') . $edit_img . '</a>';
            }

            // We can always download files.
            $item['dl'] = Horde::link(Horde::downloadUrl($val['name'], array('actionID' => 'download_file', 'dir' => $currdir, 'driver' => $GLOBALS['gollem_be']['driver'], 'file' => $val['name'])), sprintf(_("Download %s"), $val['name'])) . $download_img . '</a>';

            // Try a view link.
            $url = Horde_Util::addParameter($view_url, array('actionID' => 'view_file', 'type' => $val['type'], 'file' => $val['name'], 'dir' => $currdir, 'driver' => $GLOBALS['gollem_be']['driver']));
            $item['link'] = Horde::link('#', '', '', '_blank', Horde::popupJs($url) . 'return false;') . $name . '</a>';
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

        $vars = Horde_Variables::getDefaultVariables();
        $vars->set('page', $page);
        $pager = new Horde_Core_Ui_Pager('page', $vars, array('num' => $total, 'url' => $refresh_url, 'page_count' => 10, 'perpage' => $perpage));
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

        case 'edit':
            $hdr['width'] = '1%';
            $hdr['label'] = '&nbsp;';
            $hdr['align'] = 'center';
            $hdr['class'] = 'nosort';
            break;

        case 'download':
            $hdr['width'] = '1%';
            $hdr['label'] = '&nbsp;';
            $hdr['align'] = 'center';
            $hdr['class'] = 'nosort';
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
            $hdr['class'] = 'nosort';
            break;

        case 'owner':
            $hdr['width'] = '7%';
            $hdr['label'] = _("Owner");
            $hdr['align'] = 'right';
            $hdr['class'] = 'nosort';
            break;

        case 'group':
            $hdr['width'] = '7%';
            $hdr['label'] = _("Group");
            $hdr['align'] = 'right';
            $hdr['class'] = 'nosort';
            break;
        }

        if ($sort !== null) {
            if ($sortby == $sort) {
                $hdr['class'] = ($sortdir ? 'sortup' : 'sortdown');
            }
            $hdr['label'] = '<a href="' . Horde_Util::addParameter($refresh_url, array('actionID' => 'change_sortby', 'sortby' => $sort)) . '" class="sortlink">' . htmlspecialchars($hdr['label']) . '</a>';
        }

        $headers[] = $hdr;
    }

    /* Set up the template tags. */
    $template->set('headers', $headers, true);
    $template->set('entry', $entry, true);
    $template->set('page_caption', $page_caption);
    $template->set('filter_val', $filter);
    $template->set('checkall', Horde::getAccessKeyAndTitle(_("Check _All/None")));
} else {
    $template->set('empty_dir', true, true);
}
$template->set('itemcount', sprintf(ngettext(_("%d item"), _("%d items"), $total), $total));

$js_code = array(
    'var warn_recursive = ' . intval($GLOBALS['prefs']->getValue('recursive_deletes') == 'warn'),
);

Horde::addScriptFile('manager.js', 'gollem');
Horde::addScriptFile('tables.js', 'horde');

require GOLLEM_TEMPLATES . '/common-header.inc';
Horde::addInlineScript(implode(';', $js_code));
Gollem::menu();
Gollem::status();
echo $template->fetch(GOLLEM_TEMPLATES . '/manager/manager.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
