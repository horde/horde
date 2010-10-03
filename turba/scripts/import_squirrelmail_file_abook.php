#!/usr/bin/php
<?php
/**
 * This script imports SquirrelMail file-based addressbooks into Turba.
 * It was developed against SquirrelMail 1.4.0, so use at your own risk
 * against different versions.
 *
 * Input can be either a single squirrelmail .abook file, or a directory
 * containing multiple .abook files.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Ben Chavet <ben@horde.org>
 */

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('turba', array('authentication' => 'none', 'cli' => true, 'user_admin' => true));

// Read command line parameters.
if ($argc != 2) {
    $cli->message('Too many or too few parameters.', 'cli.error');
    $cli->writeln('Usage: import_squirrelmail_file_abook.php path-to-squirrelmail-data');
    exit;
}
$data = $argv[1];

// Get list of SquirrelMail address book files
if (is_dir($data)) {
    if (!($handle = opendir($data))) {
        exit;
    }
    $files = array();
    while (false !== ($file = readdir($handle))) {
        if (preg_match('/.abook$/', $file)) {
            $files[] = $data . '/' . $file;
        }
    }
    closedir($handle);
} else {
    $files = array($data);
}

// Loop through SquirrelMail address book files
foreach($files as $file) {
    if (!($handle = fopen($file, 'r'))) {
        continue;
    }

    // Set current user
    $user = substr(basename($file), 0, -6);
    Horde_Auth::setAuth($user, array());
    $cli->message('Importing ' . $user . '\'s address book');

    // Reset user prefs
    unset($prefs);
    $prefs = $injector->getInstance('Horde_Core_Factory_Prefs')->create('turba', array(
        'cache' => false,
        'user' => $user
    ));

    // Reset $cfgSources for current user.
    unset($cfgSources);
    include TURBA_BASE . '/config/backends.php';
    $cfgSources = Turba::getConfigFromShares($cfgSources);
    $cfgSources = Turba::permissionsFilter($cfgSources);

    // Get user's default addressbook
    $import_source = $prefs->getValue('default_dir');
    if (empty($import_source)) {
        $import_source = array_keys($cfgSources);
        $import_source = $import_source[0];
    }

    // Check existance of the specified source.
    if (!isset($cfgSources[$import_source])) {
        PEAR::raiseError(sprintf(_("Invalid address book: %s"), $import_source), 'horde.warning');
        continue;
    }

    // Initiate driver
    try {
        $driver = $GLOBALS['injector']->getInstance('Turba_Driver')->getDriver($import_source);
    } catch (Turba_Exception $e) {
        PEAR::raiseError(sprintf(_("Connection failed: %s"), $e->getMessage()), 'horde.error', null, null, $import_source);
        continue;
    }

    // Read addressbook file, one line at a time
    while (!feof($handle)) {
        $buffer = fgets($handle);
        if (empty($buffer)) {
            continue;
        }

        $entry = explode('|', $buffer);
        $members = explode(',', $entry[3]);

        if (count($members) > 1) {
            // Entry is a list of contacts, import each individually and
            // create a group that contains them.
            $attributes = array('alias' => $entry[0],
                                'firstname' => $entry[1],
                                'lastname' => $entry[2],
                                'notes' => $entry[4]);
            $gid = $driver->add($attributes);
            $group = new Turba_Object_Group($driver, array_merge($attributes, array('__key' => $gid)));
            foreach ($members as $member) {
                try {
                    $result = $driver->add(array('firstname' => $member, 'email' => $member));
                    $group->addMember($result, $import_source);
                    $cli->message('  Added ' . $member, 'cli.success');
                } catch (Turba_Exception $e) {
                    $cli->message('  ' . $e->getMessage(), 'cli.error');
                }
            }
            $group->store();
        } else {
            // entry only contains one contact, import it
            $contact = array(
                'alias' => $entry[0],
                'firstname' => $entry[1],
                'lastname' => $entry[2],
                'email' => $entry[3],
                'notes' => $entry[4]
            );

            try {
                $driver->add($contact);
                $cli->message('  Added ' . $entry[3], 'cli.success');
            } catch (Turba_Exception $e) {
                $cli->message('  ' . $e->getMessage(), 'cli.error');
            }
        }
    }

    fclose($handle);
}
