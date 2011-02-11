<?php
/**
 * This file defines reminders sent automatically by Whups (if you
 * schedule scripts/reminders.php in your crontab).
 *
 * IMPORTANT: Local overrides should be placed in reminders.local.php, or
 * reminders-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 *
 * Hopefully rule definition will migrate to a database in the future,
 * for easy configuration through the web interface.
 *
 * You can add as many reminder rules as you like. Each one is defined
 * by an array with the following indices:
 *
 *   'frequency' => A cron-style date specification defining how often
 *                  the rule will be triggered. For example,
 *                  '* 30 11 1-31&Mon *' would run any second, at half
 *                  past the hour of 11, every Monday in the month, every
 *                  month.
 *
 *   'server_name' => The hostname that reminder emails will be sent
 *                    from. Necessary since $_SERVER['SERVER_NAME'] is
 *                    not present when running from cron. You can ignore
 *                    this if you've set an explicit server name in
 *                    horde's conf.php in $conf['server']['name'].
 *
 *   'queue' => Which Whups queue are we looking at?
 *
 *   'unassigned' => What email address should we send notification of
 *                   unassigned tickets to? Can be set to false or any
 *                   empty value if no email is necessary.
 *
 *   'category' => An array of states to send reminders for. Any of
 *                 'unconfirmed', 'new', 'assigned', and 'resolved',
 *                 though I doubt you'll want to send reminders for
 *                 resolved tickets.
 */

$reminders = array();

// Here's an example entry that will send reminders for queue number 1
// every Monday at 5am, for everything but resolved tickets.
$reminders[] = array('frequency' => '* 0 5 1-31&Mon *',
                     'server_name' => 'www.example.com',
                     'queue' => 1,
                     'unassigned' => false,
                     'category' => array('unconfirmed', 'new', 'assigned'));

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/reminders.local.php')) {
    include dirname(__FILE__) . '/reminders.local.php';
}
