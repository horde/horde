#!/usr/bin/php
<?php
/**
 * This script parses MIME messages and deactivates users with returned emails.
 *
 * $Id: mail-filter.php 1234 2009-01-28 18:44:02Z duck $
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */

function usage()
{
    $argv = Console_Getopt::readPHPArgv();
    $cmd = basename($argv[0]);
    echo <<<EOU
Usage: $cmd [options]

This script parses MIME messages and deactivates users with returned emails.

Options:
    -h, --help           Give this help.
    -u, --username       A user to send notificatons with.
        --mail-host      The IMAP/POP3 server to get the messages from.
                         Defaults to "localhost".
        --mail-user      The user name for the mail server.
        --mail-pass      The password for the mail server.
        --mail-port      The mail server port. Defaults to "143".
        --mail-protocol  The mail server protocol. Defaults to "imap/notls".
        --mail-folder    The folder on the mail server. Defaults to "INBOX".

EOU;
}

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('folks', array('authentication' => 'none', 'cli' => true));

// Read command-line parameters.
$info = array();
$mail = array('host' => 'localhost',
              'pass' => '',
              'port' => 143,
              'protocol' => 'imap/notls',
              'folder' => 'INBOX');
$from_mail = false;
$options = Console_Getopt::getopt(Console_Getopt::readPHPArgv(),
                                  'h:u',
                                  array('help', 'username=',
                                        'mail-host=', 'mail-user=',
                                        'mail-pass=', 'mail-port=',
                                        'mail-protocol=', 'mail-folder='));
if ($options instanceof PEAR_Error) {
    usage();
    $cli->fatal($options->getMessage());
}

// Convert options into a hash. This is possible because all options are only
// allowed once.
$opts_hash = array();
list($opts, $args) = $options;
foreach ($opts as $opt) {
    list($optName, $optValue) = $opt;
    switch ($optName) {
        case 'h': $optName = '--help'; break;
        case 'u': $optName = '--username'; break;
    }
    $opts_hash[$optName] = is_null($optValue) ? true : $optValue;
}

// Process options in this order because some depend on others.
if (isset($opts_hash['--help'])) {
    usage();
    exit;
}
if (!isset($opts_hash['--username'])) {
    usage();
    exit;
}
foreach (array('host', 'user', 'pass', 'port', 'protocol', 'folder') as $opt) {
    if (isset($opts_hash['--mail-' . $opt])) {
        $mail[$opt] = $opts_hash['--mail-' . $opt];
    }
}

// Read and parse the message.
$messages = array();
$imap = @imap_open(sprintf('{%s:%d/%s}%s',
                            $mail['host'],
                            $mail['port'],
                            $mail['protocol'],
                            $mail['folder']),
                    $mail['user'], $mail['pass']);
if (!$imap) {
    $cli->fatal(_("Cannot authenticate at mail server:") . ' ' . implode('; ', imap_errors()));
}

// Mails from address to check
$from_str = array('Undelivered Mail', 'MAILER-DAEMON', 'root@' . $conf['server']['name']);

// Connect to db
try {
    $db = $injector->getInstance('Horde_Core_Factory_DbPear')->create();
} catch (Horde_Exception $e) {
    $cli->fatal($e);
}

// get mails
$mails = array();
foreach ($from_str as $from) {
    $mailbox = imap_search($imap, 'FROM "' . $from . '"', SE_UID);
    if ($mailbox) {
        foreach ($mailbox as $uid) {

            // Get message data
            $msg_body = imap_body($imap, $uid, FT_UID);
            $msg_body = explode("\n", $msg_body);

            // Find To: mail
            foreach ($msg_body as $line) {
                if (substr($line, 0, 3) == 'To:') {
                    $mails[] = $db->quote(trim(substr($line, 3)));
                    break;
                }
            }

            imap_delete($imap, $uid, FT_UID);
        }
    }
    imap_expunge($imap);
}

imap_close($imap);

// We have anyone to notify?
if (empty($mails)) {
    $cli->fatal(_("Have no one to notify"));
}

// Get usernames
$query = 'SELECT DISTINCT user_uid FROM folks_users WHERE user_email'
            . ' IN (' . implode(', ', array_unique($mails)) . ') ';
$users = $db->getCol($query);
if ($user_uid instanceof PEAR_Error) {
    $cli->fatal($user_uid);
    continue;
} elseif (empty($users)) {
    $cli->fatal(_("Have no one to notify"));
}

// mail content
$edit_url = Horde::url('edit/edit.php', true);
$title = _("Email problem");
$body = _("Dear %s, we tried to send you an email, but if turns out that the mail is usable any more. Maybe you run over quota. If your mail is discontinued, please update your profile with the email you are using now at %s.");

// Horde Auto login to send messages with
Horde_Auth::setAuth($opts_hash['--username'], array('transparent' => 1));

// Send messages
foreach ($users as $user) {
    $result = $registry->callByPackage(
        'letter', 'sendMessage', array($user,
                                    array('title' => $title,
                                            'content' => sprintf($body, $user_uid, $edit_url))));
    if ($result instanceof PEAR_Error) {
        $cli->message($result, 'cli.error');
    } else {
        $cli->message($user, 'cli.sucess');
    }
}

exit(0);
