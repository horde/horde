<?php
/**
 * $Horde: trean/add.php,v 1.49 2009/07/09 08:18:39 slusarz Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('trean');

/* Deal with any action task. */
$actionID = Horde_Util::getFormData('actionID');
switch ($actionID) {
case 'add_bookmark':
    /* Check permissions. */
    if (Trean::hasPermission('max_bookmarks') !== true &&
        Trean::hasPermission('max_bookmarks') <= $trean_shares->countBookmarks()) {
        $message = htmlspecialchars(sprintf(_("You are not allowed to create more than %d bookmarks."), Trean::hasPermission('max_bookmarks')));
        if (!empty($conf['hooks']['permsdenied'])) {
            $message = Horde::callHook('_perms_hook_denied', array('trean:max_bookmarks'), 'horde', $message);
        }
        $notification->push($message, 'horde.error', array('content.raw'));
        Horde::url('browse.php', true)->redirect();
    }

    $folderId = Horde_Util::getFormData('f');
    $new_folder = Horde_Util::getFormData('newFolder');

    /* Create a new folder if requested */
    if ($new_folder) {
        $properties = array();
        $properties['name'] = $new_folder;

        $parent_id = $trean_shares->getId($registry->getAuth());
        $parent = &$trean_shares->getFolder($parent_id);
        $result = $parent->addFolder($properties);

        if (is_a($result, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was an error adding the folder: %s"), $result->getMessage()), 'horde.error');
        } else {
            $folderId = $result;
        }
    }

    /* Create a new bookmark. */
    $properties = array(
        'bookmark_url' => Horde_Util::getFormData('url'),
        'bookmark_title' => Horde_Util::getFormData('title'),
        'bookmark_description' => Horde_Util::getFormData('description'),
    );

    $folder = &$trean_shares->getFolder($folderId);
    $result = $folder->addBookmark($properties);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error adding the bookmark: %s"), $result->getMessage()), 'horde.error');
    } else {
        if (Horde_Util::getFormData('popup')) {
            echo Horde::wrapInlineScript(array('window.close();'));
        } elseif (Horde_Util::getFormData('iframe')) {
            $notification->push(_("Bookmark Added"), 'horde.success');
            require TREAN_TEMPLATES . '/common-header.inc';
            $notification->notify();
        } else {
            Horde::url('browse.php', true)
                ->add('f', $folderId)
                ->redirect();
        }
        exit;
    }
    break;

case 'add_folder':
    $parent_id = Horde_Util::getFormData('f');
    if (is_null($parent_id)) {
        $parent_id = $trean_shares->getId($registry->getAuth());
    }

    /* Check permissions. */
    if (Trean::hasPermission('max_folders') !== true &&
        Trean::hasPermission('max_folders') <= Trean::countFolders()) {
        $message = htmlspecialchars(sprintf(_("You are not allowed to create more than %d folders."), Trean::hasPermission('max_folders')));
        if (!empty($conf['hooks']['permsdenied'])) {
            $message = Horde::callHook('_perms_hook_denied', array('trean:max_folders'), 'horde', $message);
        }
        $notification->push($message, 'horde.error', array('content.raw'));
        Horde::url('browse.php', true)
            ->add('f', $parent_id)
            ->redirect();
    }

    $parent = &$trean_shares->getFolder($parent_id);
    if (is_a($parent, 'PEAR_Error')) {
        $result = $parent;
    } else {
        $result = $parent->addFolder(array('name' => Horde_Util::getFormData('name')));
    }
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error adding the folder: %s"), $result->getMessage()), 'horde.error');
    } else {
        Horde::url('browse.php', true)
            ->add('f', $result)
            ->redirect();
    }
    break;
}

if (Horde_Util::getFormData('popup')) {
    Horde::addInlineScript(array(
        'window.focus()'
    ), 'dom');
}

$title = _("New Bookmark");
require TREAN_TEMPLATES . '/common-header.inc';
if (!Horde_Util::getFormData('popup') && !Horde_Util::getFormData('iframe')) {
    echo Horde::menu();
    $notification->notify(array('listeners' => 'status'));
}
require TREAN_TEMPLATES . '/add.html.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
