<?php
/**
 * An example of defining a new Kolab format type
 *
 * $Horde: framework/Kolab_Format/examples/Horde/Kolab/Format/new_type.php,v 1.4 2009/01/06 17:49:22 jan Exp $
 *
 * @package Kolab_Format
 */

/** We need the Horde_Kolab_Format package */
require_once 'Horde/Kolab/Format.php';

/** And we need the XML definition */
require_once 'Horde/Kolab/Format/XML.php';

/**
 * Kolab XML handler for a string value
 *
 * $Horde: framework/Kolab_Format/examples/Horde/Kolab/Format/new_type.php,v 1.4 2009/01/06 17:49:22 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Format
 */
class Horde_Kolab_Format_XML_string extends Horde_Kolab_Format_XML {

    /**
     * Specific data fields for the prefs object
     *
     * @var Kolab
     */
    var $_fields_specific;

    /**
     * Constructor
     */
    function Horde_Kolab_Format_XML_string()
    {
        $this->_root_name = 'string';

        /** Specific preferences fields, in kolab format specification order
         */
        $this->_fields_specific = array(
            'string' => array(
                'type' => HORDE_KOLAB_XML_TYPE_STRING,
                'value' => HORDE_KOLAB_XML_VALUE_MAYBE_MISSING,
            ),
        );

        parent::Horde_Kolab_Format_XML();
    }
}

/** Generate the format handler */
$format = Horde_Kolab_Format::factory('XML', 'string');

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

