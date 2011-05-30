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
if (file_exists(dirname(__FILE__) . '/../../ansel/lib/Application.php')) {
    $baseDir = dirname(__FILE__) . '/../';
} else {
    require_once 'PEAR/Config.php';
    $baseDir = PEAR_Config::singleton()
        ->get('horde_dir', null, 'pear.horde.org') . '/ansel/';
}
require_once $baseDir . 'lib/Application.php';
Horde_Registry::appInit('ansel', array('cli' => true));

/* Command line options */
$parser = new Horde_Argv_Parser(
    array(
        'usage' => '%prog [--options]',
        'optionList' => array(
            new Horde_Argv_Option(
                '-u',
                '--username',
                array(
                    'help' => 'Horde username'
                )
            ),
            new Horde_Argv_Option(
                '-p',
                '--password',
                array(
                    'help' => 'Horde password'
                )
            ),
            new Horde_Argv_Option(
                '-f',
                '--fields',
                array(
                    'help' => 'A \':\' delimited list of exif fields to include',
                    'default' =>  'DateTimeOriginal',
                )
            )
        )
    )
);

// Show help and exit if no arguments were set.
list($opts, $args) = $parser->parseArgs();
Horde_Registry::appInit('ansel', array('authentication' => 'none'));

// Login to horde if username & password are set.
if (!empty($opts['username']) && !empty($opts['password'])) {
    $auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();
    if (!$auth->authenticate($opts['username'], array('password' => $opts['password']))) {
        $cli->fatal(_("Username or password is incorrect."));
    } else {
        $cli->message(sprintf(_("Logged in successfully as \"%s\"."), $opts['username']), 'cli.success');
    }
} else {
    $cli->fatal(_("You must specify a valid username and password."));
}

if (!$registry->isAdmin()) {
    $cli->fatal(_("You must login with an administrative account."));
}

// Get the list of image ids that have exif data.
$sql = 'SELECT DISTINCT image_id from ansel_image_attributes;';
try {
    $image_ids = $GLOBALS['ansel_db']->selectValues($sql);
} catch (Horde_Db_Exception $e) {
    $cli->fatal($e->getMessage());
}
foreach ($image_ids as $image_id) {
    // $image = $GLOBALS['injector']
    //     ->getInstance('Ansel_Storage')
    //     ->getImage($image_id['image_id']);
    // $results = $image->exifToTags(explode($opts['fields']));
    $cli->message(sprintf(_("Extracted exif fields from %s"), $image->filename), 'cli.success');
}
$cli->message(_("Done"));
exit;
