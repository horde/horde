#!/usr/bin/env php
<?php
/**
 * This script converts the data in an SQL backend from any supported charset
 * to UTF-8.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('wicked', array('cli' => true));

// Create driver instance.
if ($conf['storage']['driver'] != 'sql') {
    exit("You must have an SQL backend configured.\n");
}
$db = $wicked->_db;

// Get current charset.
$charset = $cli->prompt('Please specify the current charset of the data',
                        null, 'ISO-8859-1');

// Read existing attachments.
echo 'Converting attachments';
$rows = $db->selectAll(
    'SELECT page_id, attachment_name, change_log FROM '
    . $conf['storage']['params']['attachmenttable']);
$updateSql =
    'UPDATE ' . $conf['storage']['params']['attachmenttable']
    . ' SET attachment_name = ?, change_log = ?'
    . ' WHERE page_id = ? AND attachment_name = ?';
foreach ($rows as $row) {
    $values = Horde_String::convertCharset(
        array($row['attachment_name'], $row['change_log']),
        $charset, 'UTF-8');
    $values[] = $row['page_id'];
    $values[] = $row['attachment_name'];
    $db->update($updateSql, $values);
    echo '.';
}

$rows = $db->selectAll(
    'SELECT page_id, attachment_name, change_log FROM '
    . $conf['storage']['params']['attachmenthistorytable']);
$updateSql =
    'UPDATE ' . $conf['storage']['params']['attachmenthistorytable']
    . ' SET attachment_name = ?, change_log = ?'
    . ' WHERE page_id = ? AND attachment_name = ?';
foreach ($rows as $row) {
    $values = Horde_String::convertCharset(
        array($row['attachment_name'], $row['change_log']),
        $charset, 'UTF-8');
    $values[] = $row['page_id'];
    $values[] = $row['attachment_name'];
    $db->update($updateSql, $values);
    echo '.';
}
$cli->writeln($cli->green('Done'));

// Read existing history.
$rows = $db->selectAll(
    'SELECT page_id, page_majorversion, page_minorversion, page_name, page_text, change_log FROM '
    . $conf['storage']['params']['historytable']);
$updateSql =
    'UPDATE ' . $conf['storage']['params']['historytable']
    . ' SET page_name = ?, page_text = ?, change_log = ?'
    . ' WHERE page_id = ? AND page_majorversion = ? AND page_minorversion = ?';
echo 'Converting history';
foreach ($rows as $row) {
    $values = Horde_String::convertCharset(
        array($row['page_name'], $row['page_text'], $row['change_log']),
        $charset, 'UTF-8');
    $values[] = $row['page_id'];
    $values[] = $row['page_majorversion'];
    $values[] = $row['page_minorversion'];
    $db->update($updateSql, $values);
}
$cli->writeln($cli->green('Done'));

// Read existing pages.
$rows = $db->selectAll(
    'SELECT page_id, page_name, page_text, change_log FROM '
    . $conf['storage']['params']['table']);
$updateSql =
    'UPDATE ' . $conf['storage']['params']['table']
    . ' SET page_name = ?, page_text = ?, change_log = ?'
    . ' WHERE page_id = ?';
echo 'Converting pages';
foreach ($rows as $row) {
    $values = Horde_String::convertCharset(
        array($row['page_name'], $row['page_text'], $row['change_log']),
        $charset, 'UTF-8');
    $values[] = $row['page_id'];
    $db->update($updateSql, $values);
    echo '.';
}

$cli->writeln($cli->green('Done'));
