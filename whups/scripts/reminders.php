#!/usr/bin/env php
<?php
/**
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/../../lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init
// some variables, etc.
Horde_Cli::init();

// Include needed libraries.
$whups_authentication = 'none';
require_once dirname(__FILE__) . '/../lib/base.php';
require_once WHUPS_BASE . '/lib/Scheduler/whups.php';

// Get an instance of the Whups scheduler.
$reminder = Horde_Scheduler::unserialize('Horde_Scheduler_whups');

// Check for and send reminders.
$reminder->run();
