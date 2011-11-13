<?php
/**
 * $Horde: trean/edit.php,v 1.59 2009/07/08 18:29:56 slusarz Exp $
 *
 * Copyright 2002-2009 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('trean');

$actionID = Horde_Util::getFormData('actionID');
if ($actionID == 'button') {
    if (Horde_Util::getFormData('new_bookmark') ||
        !is_null(Horde_Util::getFormData('new_bookmark_x'))) {
        Horde::url('add.php', true)->redirect();
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

switch ($actionID) {
case 'save':
    $bookmark_id = Horde_Util::getFormData('bookmark');
    $bookmark = $trean_gateway->getBookmark($bookmark_id);
    $old_url = $bookmark->url;

    $bookmark->url = Horde_Util::getFormData('url');
    $bookmark->title = Horde_Util::getFormData('title');
    $bookmark->description = Horde_Util::getFormData('description');
    $bookmark->tags = Horde_Util::getFormData('tags');

    if ($old_url != $bookmark->url) {
        $bookmark->http_status = '';
    }

    try {
        $result = $bookmark->save();
    } catch (Trean_Exception $e) {
        $notification->push(sprintf(_("There was an error saving the bookmark: %s"), $e->getMessage()), 'horde.error');
    }

    if (Horde_Util::getFormData('popup')) {
        if ($notification->count() <= 1) {
            echo Horde::wrapInlineScript(array('window.close();'));
        } else {
            $notification->notify();
        }
    } else {
        Horde::url('browse.php', true)
            ->redirect();
    }
    exit;

case 'delete':
    if (count($bookmarks)) {
        foreach ($bookmarks as $id) {
            $bookmark = $trean_gateway->getBookmark($id);
            $result = $trean_gateway->removeBookmark($bookmark);
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("Deleted bookmark: ") . $bookmark->title, 'horde.success');
            } else {
                $notification->push(sprintf(_("There was a problem deleting the bookmark: %s"), $result->getMessage()), 'horde.error');
            }
        }
    }

    // Return to the bookmark listing
    Horde::url('browse.php', true)->redirect();
}

// Return to browse if there is nothing to edit.
if (!count($bookmarks)) {
    $notification->push(_("Nothing to edit."), 'horde.message');
    Horde::url('browse.php', true)->redirect();
}

foreach ($bookmarks as $id) {
    $bookmark = $trean_gateway->getBookmark($id);
    break;
}

$injector->getInstance('Horde_Core_Factory_Imple')->create(
    array('trean', 'TagAutoCompleter'),
    array(
        // The name to give the (auto-generated) element that acts as the
        // pseudo textarea.
        'box' => 'treanEventACBox',

        // Make it spiffy
        'pretty' => true,

        // The dom id of the existing element to turn into a tag autocompleter
        'triggerId' => 'treanBookmarkTags',

        // A variable to assign the autocompleter object to
        'var' => 'bookmarkTagAc',

        // Tags
        'existing' => array_values($bookmark->tags),
    )
);

Horde::addInlineScript(array(
    'bookmarkTagAc.init()',
), 'dom');

$title = _("Edit Bookmark");
require $registry->get('templates', 'horde') . '/common-header.inc';
if (!Horde_Util::getFormData('popup')) {
    echo Horde::menu();
    $notification->notify(array('listeners' => 'status'));
}
require TREAN_TEMPLATES . '/edit.html.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
