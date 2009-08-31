<?php
/**
 * A sample script for reading/writing an event.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

Horde_Nls::setCharset('utf-8');

/** Generate the format handler */
$format = Horde_Kolab_Format::factory('Xml', 'Event');

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

