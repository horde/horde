<?php
/**
 * Add a new paste to the current pastebin.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.fsf.org/copyleft/bsd.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$pastie = Horde_Registry::appInit('pastie');

require_once PASTIE_BASE . '/lib/Forms/Paste.php';

$vars = Horde_Variables::getDefaultVariables();
$url = Horde::applicationUrl('paste.php');

$form = new PasteForm($vars);

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);

    try {
        $uuid = $pastie->driver->savePaste('default', $info['paste'], $info['syntax'], $info['title']);

        $notification->push(sprintf('Paste saved. %s', $uuid), 'horde.success');

        $url = Horde::applicationUrl('uuid/' . $uuid, true);
        header('Location: ' . $url);
        exit;
    } catch (Exception $e) {
        $notification->push($e->getMessage(), 'horde.error');
    }
}

$title = $form->getTitle();

require PASTIE_TEMPLATES . '/common-header.inc';
require PASTIE_TEMPLATES . '/menu.inc';

$form->renderActive(null, null, $url, 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
