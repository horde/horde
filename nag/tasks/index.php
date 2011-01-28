<?php
/**
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('nag');

$search = Horde_Util::getGet('q');
if (!$search) {
    header('HTTP/1.0 204 No Content');
    exit;
}

$tasks = Nag::listTasks(
    $prefs->getValue('sortby'),
    $prefs->getValue('sortdir'),
    $prefs->getValue('altsortby'),
    null,
    1
);
$search_pattern = '/^' . preg_quote($search, '/') . '/i';
$search_results = new Nag_Task();
$tasks->reset();
while ($task = $tasks->each()) {
    if (preg_match($search_pattern, $task->name)) {
        $search_results->add($task);
    }
}

$search_results->reset();
if ($search_results->count() == 1) {
    $task = $search_results->each();
    Horde::url($task->view_link, true)->redirect();
}

$tasks = $search_results;
$title = sprintf(_("Search: Results for \"%s\""), $search);
$actionID = null;

Horde::addScriptFile('tooltips.js', 'horde');
Horde::addScriptFile('effects.js', 'horde');
Horde::addScriptFile('quickfinder.js', 'horde');

if ($prefs->getValue('show_panel')) {
    $bodyClass = 'rightPanel';
}

require $registry->get('templates', 'horde') . '/common-header.inc';
echo Nag::menu();
Nag::status();
echo '<div id="page">';
require NAG_TEMPLATES . '/list.html.php';
require NAG_TEMPLATES . '/panel.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
