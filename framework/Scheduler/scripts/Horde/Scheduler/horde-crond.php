#!@php_bin@
<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @package Horde_Scheduler
 */

// The base file path of horde.
$horde_base = '/path/to/horde';

require_once $horde_base . '/lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none', 'cli' => true));

// Get an instance of the cron scheduler.
$daemon = Horde_Scheduler::factory('Cron');

// Now add some cron jobs to do, or add parsing to read a config file.
// $daemon->addTask('ls', '0,5,10,15,20,30,40 * * * *');

// Start the daemon going.
$daemon->run();
