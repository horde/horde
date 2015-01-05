<?php
/**
 * Handles attributes to represent a person in a simple way.
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
 * Handles attributes to represent a person in a simple way.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_Composite_SimplePerson
extends Horde_Kolab_Format_Xml_Type_Composite
{
    protected $elements = array(
        'display-name' => 'Horde_Kolab_Format_Xml_Type_String_Empty',
        'smtp-address' => 'Horde_Kolab_Format_Xml_Type_String_Empty',
        'uid'          => 'Horde_Kolab_Format_Xml_Type_String_Empty',
    );
}
