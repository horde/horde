#!/usr/bin/env php
<?php
/**
 * Script to migrate an existing 'public' Turba address book to the
 * Horde_Share based system.  This script is designed for a SQL source only.
 * This script will move *ALL* existing entries in the turba_objects table into
 * a single, globally shared Horde_Share owned by the user specified below.
 * DO NOT RUN THIS SCRIPT UNLESS you have been using 'public' => true in
 * a SQL source (such as 'localsql') - otherwise, you will turn every user's
 * private address book into a public source!
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Turba
 */

// Load Horde and Turba enviroments
require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('turba', array('authentication' => 'none', 'cli' => true, 'user_admin' => true));

$CLI->writeln('This script will turn all entries in the SQL address book into a globally shared address book.');
$CLI->writeln('Make sure you read the script comments and be sure you know what you are doing.');
$sure = $CLI->prompt('Are you ' . $CLI->bold('sure') . ' you want to do this?', array('no', 'yes'));
if (!$sure) {
    exit;
}

// Find out what our Auth driver will let us do and
// get the list of all users if we can.  If your site
// has a *large* number of users, you may want to comment
// out this section to avoid unnecessary overhead.
$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();
if ($auth->hasCapability('list')) {
    $users = $auth->listUsers();
}

// Get all the details.
do {
    $owner = $CLI->prompt('Username of the user you would like to own the new public source.');
    // Might as well check this if we have the list.
    if (!empty($users) && !in_array($owner, $users)) {
        $CLI->message($owner . ' is not a valid user!', 'cli.error');
        $owner = '';
    }
} while(!$owner);

do {
    $title = $CLI->prompt('Enter the title you would like to give to the new public source.');
} while (!$title);

$sourceKey = $CLI->prompt('What is the internal name of the share we are converting? [localsql]');
if (!$sourceKey) {
    $sourceKey = 'localsql';
}

// Create the new share.
$owner_uid = strval(new Horde_Support_Randomid());
$share = $turba_shares->newShare($GLOBALS['registry']->getAuth(), $sourceKey . ':' . $owner_uid);
$share->set('owner', $owner);
$share->set('name', $title);
$share->set('perm_default', Horde_Perms::SHOW | Horde_Perms::READ);
$turba_shares->addShare($share);
$share->save();
$CLI->message('Created new Horde_Share object for the shared address book.', 'cli.success');

// Share created, now get a Turba_Driver and make the changes.
$driver = $injector->getInstance('Turba_Injector_Factory_Driver')->create($sourceKey);

$db = &$driver->_db;

// Get the tablename in case we aren't using horde defaults.
$tableName = $db->dsn['table'];
$SQL = 'SELECT COUNT(*) FROM ' . $tableName . ';';
$count = $db->getOne($SQL);
$CLI->message("Moving $count contacts to $title.", 'cli.message');
$SQL = 'UPDATE ' . $tableName . ' SET owner_id=\'' . $owner_uid . '\';';
$result = $db->query($SQL);
if ($result instanceof PEAR_Error) {
    var_dump($result);
    exit;
}
$prefDriver = $conf['prefs']['driver'];
if ($prefDriver == 'sql') {
    // Automatically append this source to the addressbooks pref if desired.
    $autoAppend = $CLI->prompt('Would you like to add the new public source to every user\'s address book preference?', array('no', 'yes'));
    if ($autoAppend) {
        $SQL = 'SELECT pref_uid, pref_value FROM horde_prefs WHERE pref_scope=\'turba\' AND pref_name=\'addressbooks\';';
        $results = $db->getAll($SQL);
        if ($results instanceof PEAR_Error) {
           $CLI->message('There was an error updating the user preferences: ' . $results->getMessage(), 'cli.error');
        } else {
            foreach ($results as $row) {
                $newValue = $row[1] . "\n$sourceKey:$owner_uid";
                $SQL = 'UPDATE horde_prefs SET pref_value=\'' . $newValue . '\' WHERE pref_uid=\'' . $row[0] . '\' AND pref_scope=\'turba\' AND pref_name=\'addressbooks\';';
                $result = $db->query($SQL);
                if ($result instanceof PEAR_Error) {
                    $CLI->message('Could not update preferences for ' . $row[0] . ': ' . $result->getMessage(), 'cli.error');
                }
            }
        }
        if (!($results instanceof PEAR_Error)) {
            $CLI->message('Successfully added new shared address book to the user preferences.', 'cli.success');
        }
    }
} else {
    $CLI->message('Your preference backend does not support updating all user preferences.', 'cli.warning');
    $CLI->message('Your users may have to manually add the new shared address book to their "addressbook" preference.', 'cli.warning');
}

// Share our success.
$CLI->writeln($CLI->bold("*** $title successfully created ***"));
$CLI->writeln('Share Info:');
$CLI->writeln($CLI->indent('Title: ' . $share->get('name')));
$CLI->writeln($CLI->indent('Owner: ' . $share->get('owner')));
