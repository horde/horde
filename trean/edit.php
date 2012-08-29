<?php
/**
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('trean');

$bookmark_id = Horde_Util::getFormData('bookmark');
try {
    $bookmark = $trean_gateway->getBookmark($bookmark_id);
} catch (Trean_Exception $e) {
    $notification->push(sprintf(_("Bookmark not found: %s."), $e->getMessage()), 'horde.message');
    Horde::url('browse.php', true)->redirect();
}

$injector->getInstance('Horde_Core_Factory_Imple')->create('Trean_Ajax_Imple_TagAutoCompleter', array(
    'existing' => array_values($bookmark->tags)
));

$page_output->header(array(
    'title' => _("Edit Bookmark")
));
if (!Horde_Util::getFormData('popup')) {
    echo Horde::menu();
    $notification->notify(array('listeners' => 'status'));
}
require TREAN_TEMPLATES . '/edit.html.php';
$page_output->footer();
