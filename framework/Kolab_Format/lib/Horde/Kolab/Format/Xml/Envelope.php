<?php
/**
 * A Kolab XML envelope for arbitrary XML.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * A Kolab XML envelope for arbitrary XML.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Xml_Envelope extends Horde_Kolab_Format_Xml
{
    /**
     * Constructor
     */
    public function __construct($parser, $params = array())
    {
        /** Specific note fields, in kolab format specification order
         */
        $this->_fields_specific = array(
            'xml' => array(
                'type'    => self::TYPE_XML,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
        );

        parent::__construct($parser, $params);
    }

    /**
     * Convert the data to a XML stream.
     *
     * @param array $object The data array representing the object.
     *
     * @return resource The data as XML stream.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    public function save($object)
    {
        if (!isset($object['type'])) {
            throw new Horde_Kolab_Format_Exception('The "type" value is missing!');
        }
        $this->_root_name = $object['type'];
        return parent::save($object);
    }
}
