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
     * Constructor
     */
    public function __construct($parser, $params = array())
    {
        $this->_root_name = 'event';

        /** Specific event fields, in kolab format specification order
         */
        $this->_fields_specific = array(
            'summary' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'location' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'organizer' => array (
                'type' => 'Horde_Kolab_Format_Xml_Type_Composite_SimplePerson'
            ),
            'start-date' => array(
                'type'    => self::TYPE_DATE_OR_DATETIME,
                'value'   => self::VALUE_NOT_EMPTY,
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
            'show-time-as' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'color-label' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'end-date' => array(
                'type'    => self::TYPE_DATE_OR_DATETIME,
                'value'   => self::VALUE_NOT_EMPTY,
            ),
        );

        parent::__construct($parser, $params);
    }
}
