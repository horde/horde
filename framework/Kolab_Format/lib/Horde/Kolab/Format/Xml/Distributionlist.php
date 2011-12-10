<?php
/**
 * Implementation for distributionlists in the Kolab XML format.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Kolab XML handler for distributionlist groupware objects
 *
 * Copyright 2007-2009 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Distributionlist extends Horde_Kolab_Format_Xml
{
    /**
     * The name of the root element.
     *
     * @var string
     */
    protected $_root_name = 'distribution-list';

    /**
     * Specific data fields for the contact object
     *
     * @var array
     */
    protected $_fields_specific = array(
        'display-name' => array(
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_NOT_EMPTY
        ),
        'member' => 'Horde_Kolab_Format_Xml_Type_Multiple_SimplePerson',
    );

    /**
     * Load the groupware object based on the specifc XML values.
     *
     * @param array &$children An array of XML nodes.
     *
     * @return array Array with data.
     *
     * @throws Horde_Kolab_Format_Exception If parsing the XML data failed.
     */
    protected function _load(&$children)
    {
        $object = $this->_loadArray($children, $this->_fields_specific);

        // Map the display-name of a kolab dist list to horde's lastname attribute
        if (isset($object['display-name'])) {
            $object['last-name'] = $object['display-name'];
            unset($object['display-name']);
        }

        /**
         * The mapping from $object['member'] as stored in XML back to
         * Turba_Objects (contacts) must be performed in the
         * Kolab_IMAP storage driver as we need access to the search
         * facilities of the kolab storage driver.
         */
        $object['__type'] = 'Group';
        return $object;
    }

    /**
     * Save the  specifc XML values.
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
        // Map the display-name of a kolab dist list to horde's lastname attribute
        if (isset($object['last-name'])) {
            $object['display-name'] = $object['last-name'];
            unset($object['last-name']);
        }

        return $this->_saveArray($root, $object, $this->_fields_specific);
    }
}
