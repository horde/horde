<?php
/**
 * A sample script for reading/writing an event.
 *
 * $Horde: framework/Kolab_Format/examples/Horde/Kolab/Format/event.php,v 1.3 2008/08/01 07:04:52 wrobel Exp $
 *
 * @package Kolab_Format
 */

/** We need the Horde_Kolab_Format package */
require_once 'Horde/Kolab/Format.php';

/** Generate the format handler */
$format = Horde_Kolab_Format::factory('XML', 'event');

/** Prepare a test object */
$object = array(
    'uid' => 1,
    'summary' => 'test event',
    'start-date' => time(),
    'end-date' => time() + 24 * 60 * 60,
);

/** Save this test data array in Kolab XML format */
$xml = $format->save($object);
var_dump($xml);

/** Reload the object from the XML format */
$read_object = $format->load($xml);
var_dump($read_object);

