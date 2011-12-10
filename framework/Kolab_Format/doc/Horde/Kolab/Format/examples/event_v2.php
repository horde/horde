<?php
/**
 * A sample script for reading/writing an event using internal API version 2.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader/Default.php';

/** Create the factory */
$factory = new Horde_Kolab_Format_Factory();

/** Generate the format handler */
$format = $factory->create('Xml', 'Event');

$now = new DateTime();
/** Prepare a test object */
$object = array(
    'uid' => 1,
    'summary' => 'test event',
    'start-date' => array(
        'date' => $now,
    ),
    'end-date' => array(
        'date' => $now,
    )
);

/** Save this test data array in Kolab XML format */
$xml = $format->save($object);
var_dump($xml);

/** Reload the object from the XML format */
$read_object = $format->load($xml);
var_dump($read_object);

