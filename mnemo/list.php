<?php
/**
 * Copyright 2001-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Mnemo
 */
require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

try {
    $view = new Mnemo_View_List(Horde_Variables::getDefaultVariables());
} catch (Mnemo_Exception $e) {
    $notification->push($e->getMessage(), 'horde.err');
    Horde::url('list.php')->redirect();
    exit;
}
echo $view->render($page_output);

