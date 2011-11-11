<?php
/**
 * Implementation for tasks in the Kolab XML format.
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
 * Kolab XML handler for task groupware objects.
 *
 * Copyright 2007-2009 Klarälvdalens Datakonsult AB
 * Copyright 2011 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Format_Xml_Task extends Horde_Kolab_Format_Xml
{
    /**
     * The name of the root element.
     *
     * @var string
     */
    protected $_root_name = 'task';

    /**
     * Specific data fields for the prefs object
     *
     * @var Kolab
     */
    protected $_fields_specific = array(
        'summary'             => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'location'            => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'organizer'           => 'Horde_Kolab_Format_Xml_Type_Composite_SimplePerson',
        'start-date'          => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'alarm'               => 'Horde_Kolab_Format_Xml_Type_Integer',
        'recurrence'          => 'Horde_Kolab_Format_Xml_Type_Composite_Recurrence',
        'attendee'            => 'Horde_Kolab_Format_Xml_Type_Multiple_Attendee',
        'priority'            => 'Horde_Kolab_Format_Xml_Type_TaskPriority',
        'completed'           => 'Horde_Kolab_Format_Xml_Type_TaskCompletion',
        'status'              => 'Horde_Kolab_Format_Xml_Type_TaskStatus',
        'due-date'            => 'Horde_Kolab_Format_Xml_Type_DateTime',
        'parent'              => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        // These are not part of the Kolab specification but it is
        // ok if the client supports additional entries
        'creator'             => 'Horde_Kolab_Format_Xml_Type_Composite_SimplePerson',
        'percentage'          => 'Horde_Kolab_Format_Xml_Type_Integer',
        'estimate'            => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'completed_date'      => 'Horde_Kolab_Format_Xml_Type_DateTime',
        'horde-alarm-methods' => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
    );
}
