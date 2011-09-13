<?php
/**
 * A Kolab XML envelope for arbitrary XML.
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
 * A Kolab XML envelope for arbitrary XML.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
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
     * Convert the data to a XML stream. Strings contained in the data array may
     * only be provided as UTF-8 data.
     *
     * @param array $object  The data array representing the object.
     * @param array $options Additional options when parsing the XML.
     * <pre>
     * - previos: The previous XML text (default: empty string)
     * - relaxed: Relaxed error checking (default: false)
     * </pre>
     *
     * @return resource The data as XML stream.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    public function save($object, $options = array())
    {
        if (!isset($object['type'])) {
            throw new Horde_Kolab_Format_Exception('The "type" value is missing!');
        }
        $this->_root_name = $object['type'];
        return parent::save($object, $options);
    }
}
