<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('nag');

$query = Horde_Util::getGet('q');
if (!$query) {
    header('HTTP/1.0 204 No Content');
    exit;
}

$search = new Nag_Search(
    $query,
    Nag_Search::MASK_NAME,
    array('completed' => Nag::VIEW_ALL));

$search_results = $search->getSlice();
$search_results->reset();
if ($search_results->count() == 1) {
    $task = $search_results->each();
    Horde::url($task->view_link, true)->redirect();
}

$tasks = $search_results;
$actionID = null;

$page_output->addScriptFile('tooltips.js', 'horde');
$page_output->addScriptFile('scriptaculous/effects.js', 'horde');
$page_output->addScriptFile('quickfinder.js', 'horde');

$page_output->header(array(
    'body_class' => $prefs->getValue('show_panel') ? 'rightPanel' : null,
    'title' => sprintf(_("Search: Results for \"%s\""), $search)
));
echo Nag::menu();
Nag::status();
echo '<div id="page">';
require NAG_TEMPLATES . '/list.html.php';
require NAG_TEMPLATES . '/panel.inc';
$page_output->footer();
