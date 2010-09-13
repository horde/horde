<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 * @package Jonah
 */
require_once dirname(__FILE__) . '/../lib/Application.php';

Horde_Registry::appInit('jonah');
$params = array('notification' => &$notification,
                'registry' => &$registry,
                'vars' => Horde_Variables::getDefaultVariables());
$view = new Jonah_View_StoryDelete($params);
$view->run();
