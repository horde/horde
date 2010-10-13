#!/usr/bin/env php
<?php
/**
 * This script converts the data in an SQL backend from any supported charset
 * to UTF-8.
 *
 * $Horde: mnemo/scripts/upgrades/convert_to_utf8.php,v 1.4 2009/06/10 19:58:01 slusarz Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */

@define('AUTH_HANDLER', true);
@define('HORDE_BASE', dirname(__FILE__) . '/../../..');

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init some
// variables, etc.
$cli = &Horde_Cli::singleton();
$cli->init();

// Create driver instance.
@define('MNEMO_BASE', dirname(__FILE__) . '/../..');
require_once MNEMO_BASE . '/lib/base.php';
if ($conf['storage']['driver'] != 'sql') {
    exit("You must have an SQL backend configured.\n");
}
$mnemo = Mnemo_Driver::factory();
$mnemo->initialize();
$read_db = &$mnemo->_db;
$write_db = &$mnemo->_write_db;

// Get current charset.
$charset = $cli->prompt('Please specify the current charset of the data',
                        null, 'ISO-8859-1');

// Read existing notes.
$result = $read_db->query(
    'SELECT memo_owner, memo_id, memo_desc, memo_body, memo_category FROM '
    . $mnemo->_params['table']);
if (is_a($result, 'PEAR_Error')) {
    $cli->fatal($result->toString());
}
$sth = $write_db->prepare(
    'UPDATE ' . $mnemo->_params['table']
    . ' SET memo_desc = ?, memo_body = ?, memo_category = ?'
    . ' WHERE memo_owner = ? AND memo_id = ?');
echo 'Converting notes';
while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
    $values = Horde_String::convertCharset(
        array($row['memo_desc'], $row['memo_body'], $row['memo_category']),
        $charset, 'UTF-8');
    $values[] = $row['memo_owner'];
    $values[] = $row['memo_id'];
    $executed = $write_db->execute($sth, $values);
    if (is_a($executed, 'PEAR_Error')) {
        $cli->fatal($executed->toString());
    }
    echo '.';
}
$cli->writeln($cli->green('Done'));
