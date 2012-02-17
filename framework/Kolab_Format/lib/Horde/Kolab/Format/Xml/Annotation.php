<?php
/**
 * Implementation for IMAP folder annotations in the Kolab XML format.
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
 * Kolab XML handler for IMAP folder annotations.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Annotation extends Horde_Kolab_Format_Xml
{
    /**
     * The name of the root element.
     *
     * @var string
     */
    protected $_root_name = 'annotations';

    /**
     * Specific data fields for the prefs object
     *
     * @var Kolab
     */
    protected $_fields_specific = array(
        'annotation' => 'Horde_Kolab_Format_Xml_Type_Multiple_String',
    );

    /**
     * Load the groupware object based on the specifc XML values.
     *
     * @param array &$children An array of XML nodes.
     *
     * @return array Array with the object data
     *
     * @throws Horde_Kolab_Format_Exception If parsing the XML data failed.
     */
    protected function _load(&$children)
    {
        $object = $this->_loadArray($children, $this->_fields_specific);

        $result = array();
        foreach ($object['annotation'] as $annotation) {
            list($key, $value)           = split('#', $annotation, 2);
            $result[base64_decode($key)] = base64_decode($value);
        }

        return $result;
    }

    /**
     * Save the specific XML values.
     *
     * @param array $root   The XML document root.
     * @param array $object The resulting data array.
     *
     * @return boolean True on success.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    protected function _save(&$root, $object)
    {
        $annotations = array();
        foreach ($object as $key => $value) {
            if ($key != 'uid') {
                $annotations['annotation'][] = base64_encode($key) .
                    '#' . base64_encode($value);
            }
        }

        return $this->_saveArray($root, $annotations, $this->_fields_specific);
    }
}
