#!/usr/bin/env php
<?php

require_once dirname(__FILE__) . '/../lib/Application.php';
$hylax = Horde_Registry::appInit('hylax', array('authentication' => 'none', 'cli' => true));

/* Create the fax information array. Set fax_type to 0 for incoming. */
$info = array('fax_type' => 0,
              'fax_user' => '');

/* Get the arguments. The first argument is the filename from which the job ID
 * is obtained, in the format 'recvq/faxNNNNN.tif'. */
$args = Console_Getopt::readPHPArgv();
if (isset($args[1])) {
    $info['fax_id'] = $args[1];
}
if (isset($args[2])) {
    $file = $args[2];
    $info['job_id'] = (int)substr($file, 9, -4);
}

$fax_info = $cli->readStdin();
$fax_info = explode("\n", $fax_info);
foreach ($fax_info as $line) {
    $line = trim($line);
    if (preg_match('/Pages: (\d+)/', $line, $matches)) {
        $info['fax_pages'] = $matches[1];
    } elseif (preg_match('/Sender: (.+)/', $line, $matches)) {
        $info['fax_number'] = $matches[1];
    } elseif (preg_match('/Received: (\d{4}):(\d{2}):(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $line, $d)) {
        $time = mktime($d[4], $d[5], $d[6], $d[2], $d[3], $d[1]);
        $info['fax_created'] = $time;
    }
}

$t = $hylax_storage->createFax($info, true);
var_dump($t);
