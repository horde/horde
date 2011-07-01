<?php
/**
 * Handles name attributes.
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
 * Handles name attributes.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_Composite_Name
extends Horde_Kolab_Format_Xml_Type_Composite_Predefined
{
    /** Override in extending classes to set predefined parameters. */
    protected $_predefined_parameters = array(
        'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
        'array'   => array(
            'given-name' => array (
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'middle-names' => array (
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'last-name' => array (
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'full-name' => array (
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'initials' => array (
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'prefix' => array (
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'suffix' => array (
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            )
        )
    );
}
