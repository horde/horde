<?php
/**
 * Gollem clipboard script.
 *
 * Copyright 2005-2013 Horde LLC (http://www.horde.org/)
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
$template = $injector->createInstance('Horde_View');
$template->cancelbutton = _("Cancel");
$template->clearbutton = _("Clear");
$template->pastebutton = _("Paste");
$template->cutgraphic = Horde::img('cut.png', _("Cut"));
$template->copygraphic = Horde::img('copy.png', _("Copy"));
$template->currdir = Gollem::getDisplayPath($vars->dir);
$template->dir = $vars->dir;
$template->manager_url = Horde::url('manager.php');

$entry = array();
foreach ($session->get('gollem', 'clipboard') as $key => $val) {
    $entry[] = array(
        'copy' => ($val['action'] == 'copy'),
        'cut' => ($val['action'] == 'cut'),
        'id' => $key,
        'name' => $val['display']
    );
}
$template->entries = $entry;

$page_output->addScriptFile('clipboard.js');
$page_output->addScriptFile('tables.js', 'horde');
$page_output->addInlineJsVars(array(
    'GollemClipboard.selectall' => _("Select All"),
    'GollemClipboard.selectnone' => _("Select None")
));

$page_output->header(array(
    'title' => _("Clipboard")
));
$notification->notify(array('listeners' => 'status'));
echo $template->render('clipboard');
$page_output->footer();
