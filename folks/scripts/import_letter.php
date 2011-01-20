<?php
/**
 * Script to import Letter friend list
 *
 * $Id: import_letter.php 1008 2008-10-24 09:07:35Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

// Disabled by default
exit;

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('folks', array('cli' => true));

try {
    $db = $injector->getInstance('Horde_Core_Factory_DbPear')->create();
} catch (Horde_Exception $e) {
    $cli->fatal($e);
}

$sql = 'SELECT pref_uid, pref_value, pref_name FROM horde_prefs WHERE '
        . ' pref_scope = ? AND (pref_name = ? OR pref_name = ?)'
        . ' AND pref_value <> ? ORDER BY pref_uid';

$result = $db->query($sql, array('letter', 'blacklist', 'whitelist', ''));
if ($result instanceof PEAR_Error) {
    die($result);
}

$sql = 'INSERT INTO folks_friends (user_uid, group_id, friend_uid) VALUES (?, ?, ?)';
$sth = $db->prepare($sql);
if ($sth instanceof PEAR_Error) {
    die($sth);
}

while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {

    $data = array();
    $list = $row['pref_name'] == 'blacklist' ? 1 : 0;

    $users = preg_split("/[\s,]+/", $row['pref_value'], -1, PREG_SPLIT_NO_EMPTY);
    array_walk($users, '_array_clean');
    $users = array_unique($users);

    foreach ($users as $user) {
        $data[] = array($row['pref_uid'], $list, $user);
    }

    if (empty($data)) {
        continue;
    }

    $insert = $db->executeMultiple($sth, $data);
    if ($insert instanceof PEAR_Error) {
        die($insert);
    }
}

echo 'done';

/**
 * Clean usernames from garbage of old prefs letter user data
 */
function _array_clean(&$item, $key)
{
    $item = strtolower($item);
    $item = str_replace('"', '', $item);
    $item = str_replace("'", '', $item);
}
