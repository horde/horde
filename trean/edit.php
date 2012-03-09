<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('trean');

$bookmark_id = Horde_Util::getFormData('bookmark');
try {
    $bookmark = $trean_gateway->getBookmark($bookmark_id);
} catch (Trean_Exception $e) {
    $notification->push(sprintf(_("Bookmark not found: %s."), $e->getMessage()), 'horde.message');
    Horde::url('browse.php', true)->redirect();
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

$injector->getInstance('Horde_PageOutput')->addInlineScript(array(
    'bookmarkTagAc.init()',
), true);

$title = _("Edit Bookmark");
require $registry->get('templates', 'horde') . '/common-header.inc';
if (!Horde_Util::getFormData('popup')) {
    echo Horde::menu();
    $notification->notify(array('listeners' => 'status'));
}
require TREAN_TEMPLATES . '/edit.html.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
