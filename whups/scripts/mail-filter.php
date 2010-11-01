#!/usr/bin/env php
<?php
/**
 * This script accepts a MIME message on standard input or from a mail server
 * and creates a new ticket from its contents.
 */

function usage()
{
    $argv = Console_Getopt::readPHPArgv();
    $cmd = basename($argv[0]);
    echo <<<EOU
Usage: $cmd [options]

This script parses MIME messages and creates new tickets from their contents.
Single messages can be passed to the script on standard input. If the --mail
parameters are used, all messages from a mail server folder are processed
instead.

Options:
    -h, --help           Give this help.
    -a, --default-auth   A default user to set as the ticket requester, if the
                         requester cannot be determined from the message.
    -q, --queue-name     The name of the queue where the ticket should be
                         added.
    -Q, --queue-id       The (numerical) ID of the queue where the ticket
                         should be added.
    -g, --guess-queue    Guess the correct queue name from the subject. If no
                         (substring) match is found, fall back to -Q or -q.
    -k, --ticket         Add the message as a comment to this ticket instead
                         of creating a new ticket.
        --mail-host      The IMAP/POP3 server to get the messages from.
                         Defaults to "localhost".
        --mail-user      The user name for the mail server.
        --mail-pass      The password for the mail server.
        --mail-port      The mail server port. Defaults to "143".
        --mail-protocol  The mail server protocol. Defaults to "imap/notls".
        --mail-folder    The folder on the mail server. Defaults to "INBOX".

IDs are preferred over names because they are faster to process and avoid
character set ambiguities.

EOU;
}

function _dump($hash)
{
    $dump = '';
    if (empty($hash)) {
        return $dump;
    }
    $idlen = max(array_map('strlen', array_keys($hash)));
    foreach ($hash as $id => $value) {
        $dump .= sprintf("\n%${idlen}d: %s", $id, $value);
    }
    return $dump;
}

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups', array('authentication' => 'none', 'cli' => true));

// Set server name.
$conf['server']['name'] = $conf['mail']['server_name'];
$conf['server']['port'] = $conf['mail']['server_port'];

// Read command-line parameters.
$info = array();
$mail = array('host' => 'localhost',
              'pass' => '',
              'port' => 143,
              'protocol' => 'imap/notls',
              'folder' => 'INBOX');
$from_mail = false;
$options = Console_Getopt::getopt(Console_Getopt::readPHPArgv(),
                                  'ha:q:Q:gk:',
                                  array('help',
                                        'default-auth=',
                                        'queue-name=', 'queue-id=',
                                        'guess-queue',
                                        'ticket=',
                                        'mail-host=', 'mail-user=',
                                        'mail-pass=', 'mail-port=',
                                        'mail-protocol=', 'mail-folder='));
if (is_a($options, 'PEAR_Error')) {
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
        case 'a': $optName = '--default-auth'; break;
        case 'k': $optName = '--ticket'; break;
        case 'q': $optName = '--queue-name'; break;
        case 'Q': $optName = '--queue-id'; break;
        case 'g': $optName = '--guess-queue'; break;
    }
    $opts_hash[$optName] = is_null($optValue) ? true : $optValue;
}

// Process options in this order because some depend on others.
if (isset($opts_hash['--help'])) {
    usage();
    exit;
}
if (isset($opts_hash['--default-auth'])) {
    $info['default_auth'] = $opts_hash['--default-auth'];
    Horde_Auth::setAuth($info['default_auth'], array());
}
if (isset($opts_hash['--ticket'])) {
    $info['ticket'] = (int)$opts_hash['--ticket'];
}
if (isset($opts_hash['--guess-queue'])) {
    $info['guess-queue'] = true;
}
if (isset($opts_hash['--queue-name'])) {
    $queues = $whups_driver->getQueues();
    foreach ($queues as $queueId => $queueName) {
        if (strcasecmp($queueName, $opts_hash['--queue-name']) == 0) {
            $info['queue'] = $queueId;
            break;
        }
    }
}
if (isset($opts_hash['--queue-id'])) {
    $queues = $whups_driver->getQueues();
    foreach ($queues as $queueId => $queueName) {
        if (strcasecmp($queueId, $opts_hash['--queue-id']) == 0) {
            $info['queue'] = $queueId;
            break;
        }
    }
}
foreach (array('host', 'user', 'pass', 'port', 'protocol', 'folder') as $opt) {
    if (isset($opts_hash['--mail-' . $opt])) {
        $mail[$opt] = $opts_hash['--mail-' . $opt];
    }
}

// Sanity check options.
if (empty($info['ticket'])) {
    if (empty($info['queue'])) {
        usage();
        $msg = _("--queue-name or --queue-id must specify a valid and public queue.");
        if (isset($queues)) {
            $msg .= ' ' . _("Available queues:") . _dump($queues);
        }
        $cli->fatal($msg);
    }
}

// Read and parse the message.
if (empty($mail['user'])) {
    $result = Whups_Mail::processMail($cli->readStdin(), $info);
    if (is_a($result, 'PEAR_Error')) {
        $cli->fatal(_("Error processing message:") . ' ' . $result->getMessage() . ' ' . $result->getUserInfo());
    }
} else {
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
    $mailbox = imap_search($imap, 'ALL', SE_UID);
    if ($mailbox) {
        foreach ($mailbox as $uid) {
            $message = imap_fetchheader($imap, $uid, FT_UID)
                . imap_body($imap, $uid, FT_UID);
            $result = Whups_Mail::processMail($message, $info);
            if (is_a($result, 'PEAR_Error')) {
                $cli->message(_("Error processing message:") . ' ' . $result->getMessage() . ' ' . $result->getUserInfo(), 'cli.error');
            } else {
                imap_delete($imap, $uid, FT_UID);
            }
        }
    }
    imap_expunge($imap);
    imap_close($imap);
}

exit(0);
