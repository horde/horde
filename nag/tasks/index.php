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

$vars = Horde_Variables::getDefaultVariables();
$vars->search_completed = Nag::VIEW_ALL;
$vars->search_pattern = $query;
$vars->search_in = array('search_name');
$vars->actionID = 'search_tasks';
try {
    $view = new Nag_View_List($vars);
} catch (Nag_Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::url('list.php')->redirect();
    exit;
}
echo $view->render($page_output);
