#!/usr/bin/env php
<?php
/**
 * This script will check the user table and create any mail directories on the
 * system for any new users. A cron job can be set up to run this periodically.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$vilma = Horde_Registry::appInit('vilma', array('authentication' => 'none'));

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init
// some variables, etc.
Horde_Cli::init();

/* Make sure there's no compression. */
@ob_end_clean();

$users_by_domain = $vilma->driver->getAllUsers();

foreach ($users_by_domain as $domain => $users) {
    foreach ($users as $user) {
        /* Check for user's home dir. */
        if (!file_exists($user['user_home_dir'])) {
            /* Try to make the user_home_dir, if false skip. */
            if (!mkdir($user['user_home_dir'])) {
                continue;
            }
        }
        /* Check for the domain's dir. */
        $domain_dir = $user['user_home_dir'] . '/' . $domain;
        if (!file_exists($domain_dir)) {
            /* Try to make the user_home_dir, if false skip. */
            if (!mkdir($domain_dir)) {
                continue;
            }
        }
        /* Check for user's mailbox directory and if missing create it. */
        $mailbox_dir = $user['user_home_dir'] . '/' . $user['user_mail_dir'];
        if (!file_exists($mailbox_dir)) {
            system('maildirmake ' . $mailbox_dir);
        }
    }
}
