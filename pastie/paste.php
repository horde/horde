<?php
/**
 * Add a new paste to the current pastebin.
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

$vars = Horde_Variables::getDefaultVariables();
$url = Horde::url('paste.php');

$form = new PasteForm($vars);

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);

    try {
        $uuid = $pastie->driver->savePaste('default', $info['paste'], $info['syntax'], $info['title']);

        $notification->push(sprintf('Paste saved. %s', $uuid), 'horde.success');

        Horde::url('uuid/' . $uuid, true)->redirect();
    } catch (Exception $e) {
        $notification->push($e->getMessage(), 'horde.error');
    }
}

try {
    $recent = $pastie->driver->getPastes('default', 10); //FIXME: Horde_Share
} catch (Horde_Exception $e) {
    $notification->push($e);
}

$page_output->header(array(
    'title' => $form->getTitle()
));
require PASTIE_TEMPLATES . '/menu.inc';
require PASTIE_TEMPLATES . '/paste.inc';
$page_output->footer();
