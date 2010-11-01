<?php
/**
 * Implementation for IMAP folder annotations in the Kolab XML format.
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
 * Kolab XML handler for IMAP folder annotations.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Format_Xml_Annotation extends Horde_Kolab_Format_Xml
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
    public function __construct()
    {
        $this->_root_name = 'annotations';

        /**
         * Specific preferences fields, in kolab format specification order
         */
        $this->_fields_specific = array(
            'annotation' => array(
                'type'    => self::TYPE_MULTIPLE,
                'value'   => self::VALUE_MAYBE_MISSING,
                'array'   => array(
                    'type' => self::TYPE_STRING,
                    'value' => self::VALUE_MAYBE_MISSING,
                ),
            ),
        );

        parent::__construct();
    }

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
