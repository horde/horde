<?php
/**
 * Handles attributes of an attendee.
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
 * Handles attributes of an attendee.
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Format_Xml_Type_Composite_Attendee
extends Horde_Kolab_Format_Xml_Type_Composite
{
    protected $elements = array(
        'display-name'     => 'Horde_Kolab_Format_Xml_Type_String_Empty',
        'smtp-address'     => 'Horde_Kolab_Format_Xml_Type_String_Empty',
        'status'           => 'Horde_Kolab_Format_Xml_Type_AttendeeStatus',
        'request-response' => 'Horde_Kolab_Format_Xml_Type_Boolean_True',
        'role'             => 'Horde_Kolab_Format_Xml_Type_AttendeeRole',
    );
}
