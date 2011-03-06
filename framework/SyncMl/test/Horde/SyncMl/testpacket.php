#!/usr/bin/env php
<?php
/**
 * Script to test individual SyncML messages.
 *
 * The scripts takes a single client message, either XML or WBXML encoded, and
 * tries to parse it and generate a response message. It doesn't talk to any
 * backend, so it's not able to test the actualy command being sent in the
 * message. Its purpose is to make sure that SyncML messages are correctly and
 * completely parsed and distributed into the business logic.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package SyncML
 */

require_once 'SyncML.php';

class Backend extends Horde_SyncMl_Backend {

    var $_logLevel = 'DEBUG';

    function logMessage($message, $priority = 'INFO')
    {
        parent::logMessage($message, $priority);
        echo $this->_logtext;
        $this->_logtext = '';
    }

    function logFile()
    {
    }

    function _checkAuthentication($username)
    {
        return strlen($username) ? $username : true;
    }

    function setupState()
    {
        $this->state->user = 'dummyUser';
        $this->state->authenticated = true;
    }

    function addEntry($databaseURI, $content, $contentType, $cuid)
    {
        echo "Adding $cuid of $contentType to $databaseURI:\n$content\n";
    }

    function replaceEntry($databaseURI, $content, $contentType, $cuid)
    {
        echo "Replacing $cuid of $contentType in $databaseURI:\n$content\n";
    }

    function deleteEntry($databaseURI, $cuid)
    {
        echo "Deleting $cuid from $databaseURI\n";
    }

}

if (!isset($argc)) {
    die("argv/argc has to be enabled.\n");
}
if ($argc != 2) {
    die('Usage: ' . basename($argv[0]) . " syncml_client_nn.[wb]xml\n");
}

$backend = new Backend(array());
$sync = new Horde_SyncMl_ContentHandler();
$sync->debug = true;
$sync->process(file_get_contents($argv[1]), strpos($argv[1], '.wbxml') ? 'application/vnd.syncml+wbxml' : 'application/vnd.syncml');
$output = $sync->getOutput();
if (function_exists('tidy_repair_string')) {
    $output = tidy_repair_string($output, array('indent' => true, 'input-xml' => true, 'output-xml' => true));
}
echo $output, "\n";
@session_destroy();
