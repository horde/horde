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
$bookmark_id = Horde_Util::getFormData('bookmark');
if (!$bookmark_id) {
    $notification->push(_("Nothing to edit."), 'horde.message');
    Horde::url('browse.php', true)->redirect();
}
try {
    $bookmark = $trean_gateway->getBookmark($bookmark_id);
} catch (Trean_Exception $e) {
    $notification->push(sprintf(_("Bookmark not found: %s."), $e->getMessage()), 'horde.message');
    Horde::url('browse.php', true)->redirect();
}

switch ($actionID) {
case 'save':
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
