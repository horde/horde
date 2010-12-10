<?php
/**
 * Implementation for notes in the Kolab XML format.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Kolab XML handler for note groupware objects.
 *
 * Copyright 2007-2009 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Format_Xml_Note extends Horde_Kolab_Format_Xml
{
    /**
     * Specific data fields for the note object
     *
     * @var Kolab
     */
    protected $_fields_specific;

    /**
     * Constructor
     */
    public function __construct($parser, $params = array())
    {
        $this->_root_name = 'note';

        /** Specific note fields, in kolab format specification order
         */
        $this->_fields_specific = array(
            'summary' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_DEFAULT,
                'default' => '',
            ),
            'background-color' => array(
                'type'    => self::TYPE_COLOR,
                'value'   => self::VALUE_DEFAULT,
                'default' => '#000000',
            ),
            'foreground-color' => array(
                'type'    => self::TYPE_COLOR,
                'value'   => self::VALUE_DEFAULT,
                'default' => '#ffff00',
            ),
        );

        parent::__construct($parser, $params);
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

        $object['desc'] = $object['summary'];
        unset($object['summary']);

        return $object;
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
        $object['summary'] = $object['desc'];
        unset($object['desc']);

        return $this->_saveArray($root, $object, $this->_fields_specific);
    }
}
