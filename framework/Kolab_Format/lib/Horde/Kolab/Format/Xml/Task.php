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
        'summary' => array(
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_DEFAULT,
            'default' => '',
        ),
        'location' => array(
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_DEFAULT,
            'default' => '',
        ),
        'organizer' => 'Horde_Kolab_Format_Xml_Type_Composite_SimplePerson',
        'start-date' => array(
            'type'    => self::TYPE_DATE_OR_DATETIME,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'alarm' => array(
            'type'    => self::TYPE_INTEGER,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'recurrence' => 'Horde_Kolab_Format_Xml_Type_Composite_Recurrence',
        'attendee' => array(
            'type'    => self::TYPE_MULTIPLE,
            'value'   => self::VALUE_MAYBE_MISSING,
            'array'   => array(
                'type'    => 'Horde_Kolab_Format_Xml_Type_Composite_Attendee'
            ),
        ),
        'priority' => array(
            'type'    => self::TYPE_INTEGER,
            'value'   => self::VALUE_DEFAULT,
            'default' => 3,
        ),
        'completed' => array(
            'type'    => self::TYPE_INTEGER,
            'value'   => self::VALUE_DEFAULT,
            'default' => 0,
        ),
        'status' => array(
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_DEFAULT,
            'default' => 'not-started',
        ),
        'due-date' => array(
            'type'    => self::TYPE_DATE_OR_DATETIME,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'parent' => array(
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        // These are not part of the Kolab specification but it is
        // ok if the client supports additional entries
        'creator'   => 'Horde_Kolab_Format_Xml_Type_Composite_SimplePerson',
        'percentage' => array(
            'type'    => self::TYPE_INTEGER,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'estimate' => array(
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'completed_date' => array(
            'type'    => self::TYPE_DATE_OR_DATETIME,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'horde-alarm-methods' => array(
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
    );
}
