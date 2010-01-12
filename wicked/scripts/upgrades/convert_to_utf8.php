#!/usr/bin/php
<?php
/**
 * This script converts the data in an SQL backend from any supported charset
 * to UTF-8.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
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
@define('WICKED_BASE', dirname(__FILE__) . '/../..');
require_once WICKED_BASE . '/lib/base.php';
if ($conf['storage']['driver'] != 'sql') {
    exit("You must have an SQL backend configured.\n");
}
$db = &$wicked->_db;

// Get current charset.
$charset = $cli->prompt('Please specify the current charset of the data',
                        null, 'ISO-8859-1');

// Read existing attachments.
echo 'Converting attachments';
$result = $db->query(
    'SELECT page_id, attachment_name, change_log FROM '
    . $conf['storage']['params']['attachmenttable']);
if (is_a($result, 'PEAR_Error')) {
    $cli->fatal($result->toString());
}
$sth = $db->prepare(
    'UPDATE ' . $conf['storage']['params']['attachmenttable']
    . ' SET attachment_name = ?, change_log = ?'
    . ' WHERE page_id = ? AND attachment_name = ?');
while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
    $values = Horde_String::convertCharset(
        array($row['attachment_name'], $row['change_log']),
        $charset, 'UTF-8');
    $values[] = $row['page_id'];
    $values[] = $row['attachment_name'];
    $executed = $db->execute($sth, $values);
    if (is_a($executed, 'PEAR_Error')) {
        $cli->fatal($executed->toString());
    }
    echo '.';
}
$result = $db->query(
    'SELECT page_id, attachment_name, change_log FROM '
    . $conf['storage']['params']['attachmenthistorytable']);
if (is_a($result, 'PEAR_Error')) {
    $cli->fatal($result->toString());
}
$sth = $db->prepare(
    'UPDATE ' . $conf['storage']['params']['attachmenthistorytable']
    . ' SET attachment_name = ?, change_log = ?'
    . ' WHERE page_id = ? AND attachment_name = ?');
while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
    $values = Horde_String::convertCharset(
        array($row['attachment_name'], $row['change_log']),
        $charset, 'UTF-8');
    $values[] = $row['page_id'];
    $values[] = $row['attachment_name'];
    $executed = $db->execute($sth, $values);
    if (is_a($executed, 'PEAR_Error')) {
        $cli->fatal($executed->toString());
    }
    echo '.';
}
$cli->writeln($cli->green('Done'));

// Read existing history.
$result = $db->query(
    'SELECT page_id, page_majorversion, page_minorversion, page_name, page_text, change_log FROM '
    . $conf['storage']['params']['historytable']);
if (is_a($result, 'PEAR_Error')) {
    $cli->fatal($result->toString());
}
$sth = $db->prepare(
    'UPDATE ' . $conf['storage']['params']['historytable']
    . ' SET page_name = ?, page_text = ?, change_log = ?'
    . ' WHERE page_id = ? AND page_majorversion = ? AND page_minorversion = ?');
echo 'Converting history';
while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
    $values = Horde_String::convertCharset(
        array($row['page_name'], $row['page_text'], $row['change_log']),
        $charset, 'UTF-8');
    $values[] = $row['page_id'];
    $values[] = $row['page_majorversion'];
    $values[] = $row['page_minorversion'];
    $executed = $db->execute($sth, $values);
    if (is_a($executed, 'PEAR_Error')) {
        $cli->fatal($executed->toString());
    }
    echo '.';
}
$cli->writeln($cli->green('Done'));

// Read existing pages.
$result = $db->query(
    'SELECT page_id, page_name, page_text, change_log FROM '
    . $conf['storage']['params']['table']);
if (is_a($result, 'PEAR_Error')) {
    $cli->fatal($result->toString());
}
$sth = $db->prepare(
    'UPDATE ' . $conf['storage']['params']['table']
    . ' SET page_name = ?, page_text = ?, change_log = ?'
    . ' WHERE page_id = ?');
echo 'Converting pages';
while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
    $values = Horde_String::convertCharset(
        array($row['page_name'], $row['page_text'], $row['change_log']),
        $charset, 'UTF-8');
    $values[] = $row['page_id'];
    $executed = $db->execute($sth, $values);
    if (is_a($executed, 'PEAR_Error')) {
        $cli->fatal($executed->toString());
    }
    echo '.';
}
$cli->writeln($cli->green('Done'));
