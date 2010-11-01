<?php
/**
 * $Horde: trean/data.php,v 1.63 2009-11-29 15:51:42 chuck Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Trean
 */

function export($folder, $depth, $recursive = true)
{
    $name = $folder->get('name');
    if (empty($name)) {
        $name = _("Bookmarks");
    }

    $output = '';
    if ($folder->getId() != $GLOBALS['trean_shares']->getId(Horde_Auth::getAuth())) {
        $output .= sprintf('%1$s<DT><H3 FOLDED ADD_DATE="%2$s">%3$s</H3>' . "\n" . '%1$s<DL><p>' . "\n",
                           str_repeat(' ', $depth * 4), time(), $name);
    }

    $bookmarks = $folder->listBookmarks();
    foreach ($bookmarks as $bookmark) {
        $output .= sprintf('%s<DT><A HREF="%s" ADD_DATE="%s" LAST_VISIT="0" LAST_MODIFIED="0">%s</A>' . "\n",
                           str_repeat(' ', ($depth + 1) * 4),
                           $bookmark->url,
                           time(),
                           $bookmark->title);
    }

    if ($recursive) {
        $folders = Trean::listFolders(Horde_Perms::SHOW, $folder->getName(), false);
        if (is_a($folders, 'PEAR_Error')) {
            $notification->push(sprintf(_("An error occured listing folders: %s"), $folders->getMessage()), 'horde.error');
        } else {
            foreach ($folders as $subfolder) {
                $output .= export($subfolder, $depth + 1);
            }
        }
    }

    return $output . str_repeat(' ', $depth * 4) . '</DL><p>'  . "\n";
}

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('trean');

$folders_exceeded = Trean::hasPermission('max_folders') !== true &&
Trean::hasPermission('max_folders') <= Trean::countFolders();
if ($folders_exceeded) {
    $message = htmlspecialchars(sprintf(_("You are not allowed to create more than %d folders."), Trean::hasPermission('max_folders')));
    if (!empty($conf['hooks']['permsdenied'])) {
        $message = Horde::callHook('_perms_hook_denied', array('trean:max_folders'), 'horde', $message);
    }
    $notification->push($message, 'horde.warning', array('content.raw'));
}
$bookmarks_exceeded = Trean::hasPermission('max_bookmarks') !== true &&
Trean::hasPermission('max_bookmarks') <= $trean_shares->countBookmarks();
if ($bookmarks_exceeded) {
    $message = htmlspecialchars(sprintf(_("You are not allowed to create more than %d bookmarks."), Trean::hasPermission('max_bookmarks')));
    if (!empty($conf['hooks']['permsdenied'])) {
        $message = Horde::callHook('_perms_hook_denied', array('trean:max_bookmarks'), 'horde', $message);
    }
    $notification->push($message, 'horde.warning', array('content.raw'));
}

switch (Horde_Util::getFormData('actionID')) {
case 'import':
    $result = Horde_Browser::wasFileUploaded('import_file');
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result->getMessage(), 'horde.error');
        break;
    }

    $target = Horde_Util::getFormData('target', $trean_shares->getId(Horde_Auth::getAuth()));
    $root = &$trean_shares->getFolder($target);
    if (is_a($root, 'PEAR_Error')) {
        $notification->push($root, 'horde.error');
        break;
    }

    $lines = file($_FILES['import_file']['tmp_name']);

    $folders = 0;
    $bookmarks = 0;
    $folder = &$root;
    $bookmark = null;
    $stack = array();
    $max_folders = Trean::hasPermission('max_folders');
    $num_folders = Trean::countFolders();
    $stop_folders = false;
    $max_bookmarks = Trean::hasPermission('max_bookmarks');
    $num_bookmarks = $trean_shares->countBookmarks();

    foreach ($lines as $line) {
        if (strpos($line, '<DT><H3') !== false) {
            /* Start of a folder. */
            if ($stop_folders) {
                continue;
            }
            if ($max_folders !== true && $num_folders >= $max_folders) {
                $message = htmlspecialchars(sprintf(_("You are not allowed to create more than %d folders."), Trean::hasPermission('max_folders')));
                if (!empty($conf['hooks']['permsdenied'])) {
                    $message = Horde::callHook('_perms_hook_denied', array('trean:max_folders'), 'horde', $message);
                }
                $notification->push($message, 'horde.error', array('content.raw'));
                $stop_folders = true;
                continue;
            }

            $stack[] = $folder->getId();
            $folderId = $folder->addFolder(array('name' => trim(strip_tags($line))));
            $folder = &$trean_shares->getFolder($folderId);
            $bookmark = null;
            $folders++;
            $num_folders++;

        } elseif (strpos($line, '</DL>') !== false) {
            /* End of a folder. */
            $folder = &$trean_shares->getFolder(array_pop($stack));
            $bookmark = null;

        } elseif (preg_match("/<DT><A HREF=\"*(.*?)\".*>(.*)<\/A>/",
                             $line, $temp)) {
            /* A bookmark. */
            if ($max_bookmarks !== true && $num_bookmarks >= $max_bookmarks) {
                $message = htmlspecialchars(sprintf(_("You are not allowed to create more than %d bookmarks."), Trean::hasPermission('max_bookmarks')));
                if (!empty($conf['hooks']['permsdenied'])) {
                    $message = Horde::callHook('_perms_hook_denied', array('trean:max_bookmarks'), 'horde', $message);
                }
                $notification->push($message, 'horde.error', array('content.raw'));
                $stop_bookmarks = true;
                break;
            }
            $bookmark_id = $folder->addBookmark(array(
                'bookmark_url' => trim($temp[1]),
                'bookmark_title' => trim($temp[2]),
                'bookmark_description' => ''));
            $bookmark = $trean_shares->getBookmark($bookmark_id);
            $bookmarks++;
            $num_bookmarks++;
        } elseif (strpos($line, '<DD>') !== false) {
            if (!is_null($bookmark)) {
                $bookmark->description = trim(strip_tags($line));
                $bookmark->save();
                $bookmark = null;
            }
        }
    }

    $notification->push(sprintf(_("%d Folders and %d Bookmarks imported."), $folders, $bookmarks), 'horde.success');

    Horde::url('browse.php', true)
        ->add('f', $root->getId())
        ->redirect();

case 'export':
    $folderId = Horde_Util::getFormData('export_folder');
    $recursive = Horde_Util::getFormData('export_recursive');
    $output = <<<EOH
<!DOCTYPE NETSCAPE-Bookmark-file-1>
<!--This is an automatically generated file.
It will be read and overwritten.
Do Not Edit! -->
<Title>Bookmarks</Title>
<H1>Bookmarks</H1>
<DL><p>

EOH;
    $folder = $trean_shares->getFolder($folderId);
    $output .= export($folder, 1, $recursive) . '</DL><p>' . "\n";

    $browser->downloadHeaders('bookmarks.html', 'text/html', false,
                              strlen($output));
    echo $output;
    exit;
}

$title = _("Import Bookmarks");
require TREAN_TEMPLATES . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
if (!$folders_exceeded || !$bookmarks_exceeded) {
    require TREAN_TEMPLATES . '/data/import.inc';
}
require TREAN_TEMPLATES . '/data/export.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
