<?php
/**
 * Handles attributes of an address.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Handles attributes of an address.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @since Horde_Kolab_Format 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_Composite_Address
extends Horde_Kolab_Format_Xml_Type_Composite_Predefined
{
    /** Override in extending classes to set predefined parameters. */
    protected $_predefined_parameters = array(
        'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
        'array'   => array(
            'type' => array (
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => 'home',
            ),
            'street' => array (
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'locality' => array (
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'region' => array (
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'postal-code' => array (
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'country' => array (
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
        )
    );

    /**
     * Address types
     *
     * @var array
     */
    private $_address_types = array(
        'business',
        'home',
        'other',
    );

}
