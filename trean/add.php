<?php
/**
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('trean');

/* Deal with any action task. */
$actionID = Horde_Util::getFormData('actionID');
switch ($actionID) {
case 'add_bookmark':
    /* Check permissions. */
    if (Trean::hasPermission('max_bookmarks') !== true &&
        Trean::hasPermission('max_bookmarks') <= $trean_gateway->countBookmarks()) {
        Horde::permissionDeniedError(
            'trean',
            'max_bookmarks',
            sprintf(_("You are not allowed to create more than %d bookmarks."), Trean::hasPermission('max_bookmarks'))
        );
        Horde::url('browse.php', true)->redirect();
    }

    /* Create a new bookmark. */
    $properties = array(
        'bookmark_url' => Horde_Util::getFormData('url'),
        'bookmark_title' => Horde_Util::getFormData('title'),
        'bookmark_description' => Horde_Util::getFormData('description'),
        'bookmark_tags' => Horde_Util::getFormData('tags'),
    );

    try {
        $bookmark = $trean_gateway->newBookmark($properties);
    } catch (Exception $e) {
        $notification->push(sprintf(_("There was an error adding the bookmark: %s"), $e->getMessage()), 'horde.error');
    }

    if (Horde_Util::getFormData('popup')) {
        echo Horde::wrapInlineScript(array('window.close();'));
    } elseif (Horde_Util::getFormData('iframe')) {
        $notification->push(_("Bookmark Added"), 'horde.success');
        $page_output->header();
        $notification->notify();
    } else {
        Horde::url('browse.php', true)
            ->redirect();
    }
    break;
}

if (Horde_Util::getFormData('popup')) {
    $page_output->addInlineScript(array(
        'window.focus()'
    ), true);
}

$injector->getInstance('Horde_Core_Factory_Imple')->create('Trean_Ajax_Imple_TagAutoCompleter');

$page_output->header(array(
    'title' => _("New Bookmark")
));
if (!Horde_Util::getFormData('popup') && !Horde_Util::getFormData('iframe')) {
    echo Horde::menu();
    $notification->notify(array('listeners' => 'status'));
}
require TREAN_TEMPLATES . '/add.html.php';
$page_output->footer();
