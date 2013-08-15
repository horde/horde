<?php
/**
 * Passwd dynamic view.
 *
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Ralf Lang <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Passwd
 */
require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('Passwd');

$ob = new Passwd_Dynamic($injector->getInstance('Horde_Variables'));

$page_output->header(array(
    'title' => $ob->title,
    'view' => $registry::VIEW_DYNAMIC
));

$ob->render();

$page_output->footer();


