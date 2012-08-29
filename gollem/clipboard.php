<?php
/**
 * Gollem clipboard script.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('gollem');

$vars = Horde_Variables::getDefaultVariables();

/* Set up the template object. */
$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);
$template->set('cancelbutton', _("Cancel"));
$template->set('clearbutton', _("Clear"));
$template->set('pastebutton', _("Paste"));
$template->set('cutgraphic', Horde::img('cut.png', _("Cut")));
$template->set('copygraphic', Horde::img('copy.png', _("Copy")));
$template->set('currdir', Gollem::getDisplayPath($vars->dir));
$template->set('dir', $vars->dir);
$template->set('manager_url', Horde::url('manager.php'));

$entry = array();
foreach ($session->get('gollem', 'clipboard') as $key => $val) {
    $entry[] = array(
        'copy' => ($val['action'] == 'copy'),
        'cut' => ($val['action'] == 'cut'),
        'id' => $key,
        'name' => $val['display']
    );
}
$template->set('entry', $entry, true);

$page_output->addScriptFile('clipboard.js');
$page_output->addScriptFile('tables.js', 'horde');
$page_output->addInlineJsVars(array(
    'GollemClipboard.selectall' => _("Select All"),
    'GollemClipboard.selectnone' => _("Select None")
));
$menu = Gollem::menu();

$page_output->header(array(
    'title' => _("Clipboard")
));
require GOLLEM_TEMPLATES . '/javascript_defs.php';
echo $menu;
Gollem::status();
echo $template->fetch(GOLLEM_TEMPLATES . '/clipboard/clipboard.html');
$page_output->footer();
