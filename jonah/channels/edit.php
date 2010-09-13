<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('jonah');

$params = array('vars' => Horde_Variables::getDefaultVariables(),
                'registry' => &$registry,
                'notification' => &$notification);

$view = new Jonah_View_ChannelEdit($params);
$view->run();
