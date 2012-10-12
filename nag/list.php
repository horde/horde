<?php
/**
 * Nag list script.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('nag');
try {
    $view = new Nag_View_List(Horde_Variables::getDefaultVariables());
} catch (Nag_Exception $e) {
    $notification->push($e->getMessage(), 'horde.error');
    Horde::url('list.php')->redirect();
    exit;
}
echo $view->render($page_output);
