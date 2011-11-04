<?php
/**
 * A sample script for a quick speed check for reading and writing the XML data.
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

/** Prepare a test object */
if (!method_exists($format, 'getVersion')) {
    $object = array(
        'uid' => 1,
        'summary' => 'test event',
        'start-date' => time(),
        'end-date' => time() + 24 * 60 * 60,
    );
} else {
    $now = new DateTime();
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
}

$timer = new Horde_Support_Timer();
$timer->push();
for ($i = 0;$i < 1000;$i++) {
    /** Save this test data array in Kolab XML format */
    $xml = $format->save($object);
    /** Reload the object from the XML format */
    $read_object = $format->load($xml);
}

var_dump($timer->pop());
