<?php
/**
 * Gollem clipboard script.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Gollem
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('gollem');

$dir = Horde_Util::getFormData('dir');

$title = _("Clipboard");
Horde::addScriptFile('tables.js', 'horde');
require GOLLEM_TEMPLATES . '/common-header.inc';
Gollem::menu();
Gollem::status();

$entry = array();
foreach ($_SESSION['gollem']['clipboard'] as $key => $val) {
    $entry[] = array(
        'copy' => ($val['action'] == 'copy'),
        'cut' => ($val['action'] == 'cut'),
        'id' => $key,
        'name' => $val['display']
    );
}

/* Set up the template object. */
$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);
$template->set('cancelbutton', _("Cancel"));
$template->set('clearbutton', _("Clear"));
$template->set('pastebutton', _("Paste"));
$template->set('cutgraphic', Horde::img('cut.png', _("Cut")));
$template->set('copygraphic', Horde::img('copy.png', _("Copy")));
$template->set('currdir', Gollem::getDisplayPath($dir));
$template->set('dir', $dir);
$template->set('entry', $entry, true);
$template->set('manager_url', Horde::url('manager.php'));

echo $template->fetch(GOLLEM_TEMPLATES . '/clipboard/clipboard.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
