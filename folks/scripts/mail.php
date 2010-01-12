<?php
/**
 * Send mail to a user that has new messages
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */

// Do CLI checks and environment setup first.
require_once 'Horde/Cli.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment.
Horde_Cli::init();
$cli = Horde_Cli::singleton();

// Load Folks.
$folks_authentication = 'none';
$no_compress = true;
require_once dirname(__FILE__) . '/../lib/base.php';

// We accept the user name on the command-line.
$ret = Console_Getopt::getopt(Console_Getopt::readPHPArgv(), 'h:u:p:dt:f:c:',
                              array('help', 'username=', 'password=', 'time=', 'from=', 'count='));

if ($ret instanceof PEAR_Error) {
    $error = _("Couldn't read command-line options.");
    Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_DEBUG);
    $cli->fatal($error);
}

// Show help and exit if no arguments were set.
list($opts, $args) = $ret;
if (!$opts) {
    showHelp();
    exit;
}

foreach ($opts as $opt) {
    list($optName, $optValue) = $opt;
    switch ($optName) {
    case 'u':
    case '--username':
        $username = $optValue;
        break;

    case 'p':
    case '--password':
        $password = $optValue;
        break;

    case 'f':
    case '--from':
        $from = (int)$optValue;

    case 'c':
    case '--count':
        $count = (int)$optValue;
        break;

    case 'h':
    case '--help':
        showHelp();
        exit;
    }
}

// Login to horde if username & password are set.
if (!empty($username) && !empty($password)) {
    $auth = Horde_Auth::singleton($conf['auth']['driver']);
    if (!$auth->authenticate($username, array('password' => $password))) {
        $error = _("Login is incorrect.");
        Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_ERR);
        $cli->fatal($error);
    } else {
        $msg = sprintf(_("Logged in successfully as \"%s\"."), $username);
        Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $cli->message($msg, 'cli.success');
    }
}

// Only admins can run this operation
if (!Horde_Auth::isAdmin('folks:admin')) {
    $cli->fatal('ADMIN ONLY');
}

// Connect to db
$dbconf = Horde::getDriverConfig('storage', 'sql');
$db = DB::connect($dbconf);

// Get new messages older time
$query = 'SELECT user_uid, user_email FROM folks_users ORDER BY user_uid ASC';

if (isset($count)) {
    $db->modifyLimitQuery($query, $from, $count);
}

$res = $db->query($query);
if ($res instanceof PEAR_Error) {
    $cli->fatal($res);
}

$cli->message($res->numRows(), 'cli.success');

$subject = sprintf('News on %s', $registry->get('name', 'horde'));
$body = "Hello %s,\n\n There is someting new on %s\n\n. Visit us at %s";

// Prepare data for bash process or delete one by one
$paths = array();
while ($row =& $res->fetchRow()) {

    $body2 = sprintf($body, $row[0], $registry->get('name', 'horde'), Folks::getUrlFor('user', $row[0], true, -1));

    // Send mail
    $mail = new MIME_Mail($subject, $body2, $row[1], $conf['support'], Horde_Nls::getCharset());
    $mail->addHeader('User-Agent', 'Folks' . $registry->getVersion());
    $sent = $mail->send($conf['mailer']['type'], $conf['mailer']['params']);
    if ($sent instanceof PEAR_Error) {
        $cli->message($sent, 'cli.warning');
    } else {
        $cli->message($row[0], 'cli.success');
    }

    // sleep(1);
}

$cli->message('done', 'cli.success');

/**
 * Show the command line arguments that the script accepts.
 */
function showHelp()
{
    global $cli;

    $cli->writeln(sprintf(_("Usage: %s [OPTIONS]..."), basename(__FILE__)));
    $cli->writeln();
    $cli->writeln(_("Mandatory arguments to long options are mandatory for short options too."));
    $cli->writeln();
    $cli->writeln(_("-h, --help                   Show this help"));
    $cli->writeln(_("-c, --count                  Limit count"));
    $cli->writeln(_("-f, --from                   Limit offset"));
    $cli->writeln(_("-u, --username[=username]    Horde login username"));
    $cli->writeln(_("-p, --password[=password]    Horde login password"));
    $cli->writeln();
}
