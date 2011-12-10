<?php
/**
 * An example of defining a new Kolab format type
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

/**
 * Kolab XML handler for a string value
 *
 * Copyright 2008-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_String extends Horde_Kolab_Format_Xml
{

    /**
     * Specific data fields for the prefs object
     *
     * @var Kolab
     */
    protected $_fields_specific;

    /**
     * Constructor
     */
    public function __construct($parser, $params = array())
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

        parent::__construct($parser, $params);
    }
}

/** Create the factory */
$factory = new Horde_Kolab_Format_Factory();

/** Generate the format handler */
$format = $factory->create('Xml', 'String');

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

