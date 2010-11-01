<?php
/**
 * An example of defining a new Kolab format type
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

/**
 * Kolab XML handler for a string value
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Format_Xml_String extends Horde_Kolab_Format_Xml
{

    /**
     * Specific data fields for the prefs object
     *
     * @var Kolab
     */
    var $_fields_specific;

    /**
     * Constructor
     */
    function __construct()
    {
        $this->_root_name = 'string';

        /** Specific preferences fields, in kolab format specification order
         */
        $this->_fields_specific = array(
            'string' => array(
                'type' => self::TYPE_STRING,
                'value' => self::VALUE_MAYBE_MISSING,
            ),
        );

        parent::__construct();
    }
}

/** Generate the format handler */
$format = Horde_Kolab_Format::factory('Xml', 'String');

/** Prepare a test object */
$object = array(
    'uid' => 1,
    'string' => 'test string',
);

/** Save this test data array in Kolab XML format */
$xml = $format->save($object);
var_dump($xml);

/** Reload the object from the XML format */
$read_object = $format->load($xml);
var_dump($read_object);

