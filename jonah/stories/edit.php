<?php
/**
 * Script to add/edit stories.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 * @package Jonah
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('jonah');

$params = array('notification' => &$notification,
                'registry' => &$registry,
                'vars' => Horde_Variables::getDefaultVariables());
$view = new Jonah_View_StoryEdit($params);
$view->run();
