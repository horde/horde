<?php
/**
 * View a paste.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

require_once __DIR__ . '/lib/Application.php';
$pastie = Horde_Registry::appInit('pastie');

require_once PASTIE_BASE . '/lib/Forms/Paste.php';
$uuid = Horde_Util::getFormData('uuid');

if (!empty($uuid)) {
    try {
        $paste = $pastie->driver->getPaste(array('uuid' => $uuid));
        $recent = $pastie->driver->getPastes('default', 10); //FIXME: Horde_Share
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $paste = null;
    }
}

$pasteurl = Horde::url('paste.php');
$vars = new Horde_Variables($paste);
$form = new PasteForm($vars);

try {
    $engine = 'Pastie_Highlighter_' . $GLOBALS['conf']['highlighter']['engine'];
    $output = call_user_func_array(array($engine, 'output'), array($paste['paste'], $paste['syntax']));
} catch (Pastie_Exception $e) {
    $output = _("Error parsing the paste.");
}

$page_output->header(array(
    'title' => _("View Paste")
));
$notification->notify(array('listeners' => 'status'));
require PASTIE_TEMPLATES . '/view.inc';
$form->renderActive(null, null, $pasteurl, 'post');
$page_output->footer();
