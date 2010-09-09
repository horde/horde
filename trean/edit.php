<?php
/**
 * $Horde: trean/edit.php,v 1.59 2009/07/08 18:29:56 slusarz Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('trean');

$folderId = Horde_Util::getFormData('f', $trean_shares->getId($GLOBALS['registry']->getAuth()));

$actionID = Horde_Util::getFormData('actionID');
if ($actionID == 'button') {
    if (Horde_Util::getFormData('new_bookmark') ||
        !is_null(Horde_Util::getFormData('new_bookmark_x'))) {
        Horde::url('add.php', true)->add('f', $folderId)->redirect();
    }
    if (Horde_Util::getFormData('edit_bookmarks')) {
        $actionID = null;
    } elseif (Horde_Util::getFormData('delete_bookmarks') ||
              !is_null(Horde_Util::getFormData('delete_bookmarks_x'))) {
        $actionID = 'delete';
    }
}

$bookmarks = Horde_Util::getFormData('bookmarks');
if (!is_array($bookmarks)) {
    $bookmarks = array($bookmarks);
}
$folder = Horde_Util::getFormData('folder');

switch ($actionID) {
case 'save':
    $url = Horde_Util::getFormData('url');
    $title = Horde_Util::getFormData('title');
    $description = Horde_Util::getFormData('description');
    $new_folder = Horde_Util::getFormData('new_folder');
    $delete = Horde_Util::getFormData('delete');
    if (count($bookmarks)) {
        foreach ($bookmarks as $id) {
            $bookmark = $trean_shares->getBookmark($id);
            if (isset($delete[$id])) {
                $result = $trean_shares->removeBookmark($bookmark);
                if (!is_a($result, 'PEAR_Error')) {
                    $notification->push(_("Deleted bookmark: ") . $bookmark->title, 'horde.success');
                } else {
                    $notification->push(sprintf(_("There was a problem deleting the bookmark: %s"), $result->getMessage()), 'horde.error');
                }
            } else {
                $old_url = $bookmark->url;

                $bookmark->url = $url[$id];
                $bookmark->title = $title[$id];
                $bookmark->description = $description[$id];

                if ($old_url != $bookmark->url) {
                    $bookmark->http_status = '';
                }

                $result = $bookmark->save();

                if ($new_folder[$id] != $bookmark->folder) {
                    $bookmark->folder = $new_folder[$id];
                    $result = $bookmark->save();
                }

                if (is_a($result, 'PEAR_Error')) {
                    $notification->push(sprintf(_("There was an error saving the bookmark: %s"), $result->getMessage()), 'horde.error');
                }
            }
        }
    }

    if (count($folder)) {
        $name = Horde_Util::getFormData('name');
        foreach ($folder as $id) {
            $folder = &$trean_shares->getFolder($id);
            $folder->set('name', $name[$id], true);
            $result = $folder->save();
            if (is_a($result, 'PEAR_Error')) {
                $notification->push(sprintf(_("There was an error saving the folder: %s"), $result->getMessage()), 'horde.error');
            }
        }
    }

    if (Horde_Util::getFormData('popup')) {
        if ($notification->count() <= 1) {
            echo Horde::wrapInlineScript(array('window.close();'));
        } else {
            $notification->notify();
        }
    } else {
        Horde::url('browse.php', true)
            ->add('f', $folderId)
            ->redirect();
    }
    exit;

case 'delete':
    if (count($bookmarks)) {
        foreach ($bookmarks as $id) {
            $bookmark = $trean_shares->getBookmark($id);
            $result = $trean_shares->removeBookmark($bookmark);
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("Deleted bookmark: ") . $bookmark->title, 'horde.success');
            } else {
                $notification->push(sprintf(_("There was a problem deleting the bookmark: %s"), $result->getMessage()), 'horde.error');
            }
        }
    }

    if (count($folder)) {
        foreach ($folder as $id => $delete) {
            if ($delete) {
                $folder = &$trean_shares->getFolder($id);
                $result = $folder->delete();
                if (!is_a($result, 'PEAR_Error')) {
                    $notification->push(_("Deleted folder: ") . $folder->get('name'), 'horde.success');
                } else {
                    $notification->push(sprintf(_("There was a problem deleting the folder: %s"), $result->getMessage()), 'horde.error');
                }
            }
        }
    }

    // Return to the folder listing
    Horde::url('browse.php', true)->add('f', $folderId)->redirect();

case 'move':
    $create_folder = Horde_Util::getFormData('create_folder');
    $new_folder = Horde_Util::getFormData('new_folder');

    /* Create a new folder if requested */
    if ($create_folder) {
        $parent_id = $trean_shares->getId($GLOBALS['registry']->getAuth());
        $parent = &$trean_shares->getFolder($parent_id);
        $result = $parent->addFolder(array('name' => $new_folder));

        if (is_a($result, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was an error adding the folder: %s"), $result->getMessage()), 'horde.error');
        } else {
            $new_folder = $result;
        }
    }

    $new_folder = &$trean_shares->getFolder($new_folder);

    if (count($bookmarks)) {
        foreach ($bookmarks as $id) {
            $bookmark = $trean_shares->getBookmark($id);
            $bookmark->folder = $new_folder->getId();
            $result = $bookmark->save();
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("Moved bookmark: ") . $bookmark->title, 'horde.success');
            } else {
                $notification->push(sprintf(_("There was a problem moving the bookmark: %s"), $result->getMessage()), 'horde.error');
            }
        }
    }

    if (count($folder)) {
        foreach ($folder as $id => $delete) {
            if ($delete) {
                $folder = &$trean_shares->getFolder($id);
                $result = $trean_shares->move($folder, $new_folder);
                if (!is_a($result, 'PEAR_Error')) {
                    $notification->push(_("Moved folder: ") . $folder->get('name'), 'horde.success');
                } else {
                    $notification->push(sprintf(_("There was a problem moving the folder: %s"), $result->getMessage()), 'horde.error');
                }
            }
        }
    }

    // Return to the folder listing
    Horde::url('browse.php', true)->add('f', $folderId)->redirect();

case 'copy':
    $create_folder = Horde_Util::getFormData('create_folder');
    $new_folder = Horde_Util::getFormData('new_folder');

    /* Create a new folder if requested */
    if ($create_folder) {
        $properties = array();
        $properties['name'] = $new_folder;

        $parent_id = $trean_shares->getId($GLOBALS['registry']->getAuth());
        $parent = &$trean_shares->getFolder($parent_id);
        $result = $parent->addFolder($properties);

        if (is_a($result, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was an error adding the folder: %s"), $result->getMessage()), 'horde.error');
        } else {
            $new_folder = $result;
        }
    }

    $new_folder = &$trean_shares->getFolder($new_folder);

    if (count($bookmarks)) {
        foreach ($bookmarks as $id) {
            $bookmark = $trean_shares->getBookmark($id);
            $result = $bookmark->copyTo($new_folder);
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("Copied bookmark: ") . $bookmark->title, 'horde.success');
            } else {
                $notification->push(sprintf(_("There was a problem copying the bookmark: %s"), $result->getMessage()), 'horde.error');
            }
        }
    }

    if (count($folder)) {
        $notification->push(sprintf(_("Copying folders is not supported.")), 'horde.message');
    }

    // Return to the folder listing
    Horde::url('browse.php', true)->add('f', $folderId)->redirect();

case 'rename':
    /* Rename a Bookmark Folder. */
    $name = Horde_Util::getFormData('name');

    $folder = &$trean_shares->getFolder($folderId);
    $result = $folder->set('name', $name, true);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("\"%s\" was not renamed: %s."), $name, $result->getMessage()), 'horde.error');
    } else {
        Horde::url('browse.php', true)->add('f', $folderId)->redirect();
    }
    break;

case 'del_folder':
    $folder = &$trean_shares->getFolder($folderId);
    $title = _("Confirm Deletion");
    require TREAN_TEMPLATES . '/common-header.inc';
    echo Horde::menu();
    $notification->notify(array('listeners' => 'status'));
    require TREAN_TEMPLATES . '/edit/delete_folder_confirmation.inc';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;

case 'del_folder_confirmed':
    $folderId = Horde_Util::getPost('f');
    if (!$folderId) {
        exit;
    }

    $folder = &$trean_shares->getFolder($folderId);
    if (is_a($folder, 'PEAR_Error')) {
        $notification->push($folder->getMessage(), 'horde.error');
        Horde::url('browse.php')->redirect();
    }

    $parent = $folder->getParent();
    $result = $folder->delete();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result->getMessage(), 'horde.error');
    } else {
        $notification->push(sprintf(_("Deleted the folder \"%s\""), $folder->get('name')), 'horde.success');
    }
    Horde::url('browse.php', true)->add('f', $folderId)->redirect();
    exit;

case 'cancel':
    Horde::url('browse.php', true)->add('f', $folderId)->redirect();
}

// Return to browse if there is nothing to edit.
if (!count($bookmarks) && !count($folder)) {
    $notification->push(_("Nothing to edit."), 'horde.message');
    Horde::url('browse.php', true)->add('f', $folderId)->redirect();
}

$title = _("Edit Bookmark");
require TREAN_TEMPLATES . '/common-header.inc';
if (!Horde_Util::getFormData('popup')) {
    echo Horde::menu();
    $notification->notify(array('listeners' => 'status'));
}
require TREAN_TEMPLATES . '/edit/header.inc';

if (count($folder)) {
    foreach ($folder as $id) {
        $folder = $trean_shares->getFolder($id);
        require TREAN_TEMPLATES . '/edit/folder.inc';
    }
}

if (count($bookmarks)) {
    foreach ($bookmarks as $id) {
        $bookmark = $trean_shares->getBookmark($id);
        if (!is_a($bookmark, 'PEAR_Error')) {
            require TREAN_TEMPLATES . '/edit/bookmark.inc';
        }
    }
}

require TREAN_TEMPLATES . '/edit/footer.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
