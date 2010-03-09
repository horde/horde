#!/usr/bin/env php
<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups', array('authentication' => 'none'));

// Get an instance of the Whups scheduler.
$reminder = Horde_Scheduler::unserialize('Horde_Scheduler_Whups');

// Check for and send reminders.
$reminder->run();
