<?php
/**
 * Implementation for events in the Kolab XML format.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Kolab XML handler for event groupware objects.
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
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Format_Xml_Event extends Horde_Kolab_Format_Xml
{
    /**
     * The name of the root element.
     *
     * @var string
     */
    protected $_root_name = 'event';

    /**
     * Specific data fields for the prefs object
     *
     * @var Kolab
     */
    protected $_fields_specific = array(
        'summary'      => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'location'     => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'organizer'    => 'Horde_Kolab_Format_Xml_Type_Composite_SimplePerson',
        'start-date'   => 'Horde_Kolab_Format_Xml_Type_EventDate',
        'alarm'        => 'Horde_Kolab_Format_Xml_Type_Integer',
        'recurrence'   => 'Horde_Kolab_Format_Xml_Type_Composite_Recurrence',
        'attendee'     => 'Horde_Kolab_Format_Xml_Type_Multiple_Attendee',
        'show-time-as' => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'color-label'  => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'end-date'     => 'Horde_Kolab_Format_Xml_Type_EventDate',
    );
}
