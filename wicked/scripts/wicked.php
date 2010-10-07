#!/usr/bin/php -q
<?php
/**
* This script interfaces with Wicked via the command-line
*
* See the enclosed file COPYING for license information (GPL). If you
* did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
*
* @author Vijay Mahrra <vijay.mahrra@es.easynet.net>
*/

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('wicked', array('authentication' => 'none', 'cli' => true));

$debug = false;
$out = 'screen';

// We accept the user name on the command-line.
$ret = Console_Getopt::getopt(Console_Getopt::readPHPArgv(), 'hu::p::ldg::o::',
                              array('help', 'username==', 'password==', 'list-pages',
                                'debug', 'get==', 'out=='));

if (is_a($ret, 'PEAR_Error')) {
    $error = _("Couldn't read command-line options.");
    Horde::logMessage($error, 'DEBUG');
    $cli->fatal($error);
}

list($opts, $args) = $ret;

// Show help and exit if no arguments were set.
if (!count($opts)) {
    showHelp();
    exit;
}

foreach ($opts as $opt) {
    list($optName, $optValue) = $opt;
    switch ($optName) {
    case 'd':
    case '--debug':
        $debug = true;
        break;

    case 'u':
    case '--username':
        $username = $optValue;
        break;

    case 'p':
    case '--password':
        $password = $optValue;
        break;

    case 'l':
    case '--list-pages':
        $listpages = true;
        break;

    case 'g':
    case '--get':
        $get = $optValue;
        break;

    case 'o':
    case '--out':
        $out = $optValue;
        break;

    default:
    case 'h':
    case '--help':
        showHelp();
        exit;
    }
}

// Login to horde if username & password are set.
if (!empty($username) && !empty($password)) {
    $auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();
    if (!$auth->authenticate($username, array('password' => $password))) {
        $error = _("Login is incorrect.");
        Horde::logMessage($error, 'ERR');
        $cli->message($msg, 'cli.error');
    } else {
        $msg = sprintf(_("Logged in successfully as \"%s\"."), $username);
        Horde::logMessage($msg, 'DEBUG');
        $cli->message($msg, 'cli.success');
    }
}

// list page titles only
if (!empty($listpages)) {
    $pages = $wicked->getPages();
    foreach ($pages as $page) {
        $cli->writeln($page);
    }
    exit;
}

// retrieve a user-specified page or all of them
if (!is_null($get)) {
    $results = $wicked->getPage($get);
    if (empty($results)) {
        $error = sprintf(_("page \"%s\" doesn't exist."), $get);
        $cli->message($error, 'cli.error');
        exit;
    }
} elseif (is_null($get)) {
    $results = $wicked->getAllPages();
}

// if we have a list of pages set, output them
switch ($out) {
default:
case 'xml':
    if (isset($results) && count($results) > 0) {
        $cli->writeln("<wicked:wikipage>");
        foreach ($results as $page) {
            foreach ($page as $k => $v) {
                $cli->writeln(sprintf("<wicked:%s>%s</wicked:%s>", $k, htmlentities($v), $k));
            }
        }
        $cli->writeln("</wicked:wikipage>");
        $cli->writeln();
    }
    break;
}
exit;

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
    $cli->writeln(_("-d, --debug                  Run in debug mode (displays extra information)"));
    $cli->writeln(_("-l, --list-pages             List pages"));
    $cli->writeln(_("-g, --get[=pagetitle]        Return all pages (unless pagetitle specified)"));
    $cli->writeln(_("-o, --out[=type]             Output type for results (default:xml)"));
    $cli->writeln(_("-u, --username[=username]    Horde login username"));
    $cli->writeln(_("-p, --password[=password]    Horde login password"));
    $cli->writeln();
}
