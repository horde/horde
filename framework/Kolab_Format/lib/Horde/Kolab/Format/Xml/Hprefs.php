<?php
/**
 * Implementation for horde user preferences in the Kolab XML format.
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
 * Kolab XML handler for client preferences.
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 * @since    Horde 3.2
 */
class Horde_Kolab_Format_Xml_Hprefs extends Horde_Kolab_Format_Xml
{
    /**
     * Specific data fields for the prefs object
     *
     * @var Kolab
     */
    protected $_fields_specific;

    /**
     * Automatically create categories if they are missing?
     *
     * @var boolean
     */
    protected $_create_categories = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_root_name = 'h-prefs';

        /** Specific preferences fields, in kolab format specification order
         */
        $this->_fields_specific = array(
            'application' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'pref' => array(
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
     * Load an object based on the given XML string.
     *
     * @param string &$xmltext The XML of the message as string.
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Exception If parsing the XML data failed.
     */
    public function load(&$xmltext)
    {
        $object = parent::load($xmltext);

        if (empty($object['application'])) {
            if (!empty($object['categories'])) {
                $object['application'] = $object['categories'];
                unset($object['categories']);
            } else {
                throw new Horde_Exception('Preferences XML object is missing an application setting.');
            }
        }

        return $object;
    }

    /**
     * Convert the data to a XML string.
     *
     * @param array $object The data array representing the note.
     *
     * @return string The data as XML string.
     *
     * @throws Horde_Exception If converting the data to XML failed.
     */
    public function save($object)
    {
        if (empty($object['application'])) {
            if (!empty($object['categories'])) {
                $object['application'] = $object['categories'];
                unset($object['categories']);
            } else {
                throw new Horde_Exception('Preferences XML object is missing an application setting.');
            }
        }

        return parent::save($object);
    }
}
