#!/usr/bin/env php
<?php
/**
* Bare bones script to auto append an image's exif fields to it's tags.
*
* See the enclosed file COPYING for license information (GPL). If you
* did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
*
* @author Michael J. Rubinsky <mrubinsk@horde.org>
*/

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('ansel', array('authentication' => 'none', 'cli' => true));

/* Command line options */
$ret = Console_Getopt::getopt(Console_Getopt::readPHPArgv(), 'hu:p:f:',
                              array('help', 'username=', 'password=', 'fields='));

if ($ret instanceof PEAR_Error) {
    $cli->fatal($ret->getMessage());
}

/* Show help and exit if no arguments were set. */
list($opts, $args) = $ret;
if (!$opts) {
    showHelp();
    exit;
}

// Default to only DateTimeOriginal
$exif_fields = array('DateTimeOriginal');
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
    case 'h':
    case '--help':
        showHelp();
        exit;
    case '--fields':
    case 'f':
        $exif_fields = explode(':', $optValue);
        break;
    }
}

Horde_Registry::appInit('ansel', array('authentication' => 'none'));

// Login to horde if username & password are set.
if (!empty($username) && !empty($password)) {
    $auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();
    if (!$auth->authenticate($username, array('password' => $password))) {
        $cli->fatal(_("Username or password is incorrect."));
    } else {
        $cli->message(sprintf(_("Logged in successfully as \"%s\"."), $username), 'cli.success');
    }
} else {
    $cli->fatal(_("You must specify a valid username and password."));
}

if (!$registry->isAdmin()) {
    $cli->fatal(_("You must login with an administrative account."));
}

// Get the list of image ids that have exif data.
$sql = 'SELECT DISTINCT image_id from ansel_image_attributes;';
$results = $GLOBALS['ansel_db']->query($sql);
if ($results instanceof PEAR_Error) {
    $cli->fatal($results->getMessage());
}
$image_ids = $results->fetchAll(MDB2_FETCHMODE_ASSOC);
$results->free();
foreach (array_values($image_ids) as $image_id) {
    $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage($image_id['image_id']);
    $results = $image->exifToTags($exif_fields);
    $cli->message(sprintf(_("Extracted exif fields from %s"), $image->filename), 'cli.success');
}
$cli->message(_("Done"));
exit;

function showHelp()
{
    global $cli;

    $cli->writeln(sprintf(_("Usage: %s [OPTIONS]..."), basename(__FILE__)));
    $cli->writeln();
    $cli->writeln(_("Mandatory arguments to long options are mandatory for short options too."));
    $cli->writeln();
    $cli->writeln(_("-h, --help                   Show this help"));
    $cli->writeln(_("-u, --username[=username]    Horde login username"));
    $cli->writeln(_("-p, --password[=password]    Horde login password"));
    $cli->writeln(_("-f, --fields[=exif_fields]   A ':' delimited list of exif fields to include DateTimeOriginal is default."));
}
