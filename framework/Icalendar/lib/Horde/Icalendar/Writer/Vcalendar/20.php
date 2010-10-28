<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Icalendar
 */

/**
 * A writer class for exporting iCalendar 2.0 (RFC 2445) data.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Icalendar
 */
class Horde_Icalendar_Writer_Vcalendar_20 extends Horde_Icalendar_Writer_Base
{
    /**
     * A hash that maps property names of Horde_Icalendar objects to property
     * names defined in RFCs.
     *
     * @var array
     */
    protected $_propertyMap = array('product' => 'PRODID',
                                    'start' => 'DTSTART',
                                    'stamp' => 'DTSTAMP');

    /**
     * Converts a property value of an object to a string
     *
     * @param object $property  A property value.
     * @param array $params     Property parameters.
     *
     * @return string  The string representation of the object.
     */
    protected function _objectToString($property, array $params)
    {
        if ($property instanceof Horde_Date) {
            if (isset($params['value']) && $params['value'] == 'date') {
                return $property->format('Ymd');
            }
            return $property->format('Ymd\THis\Z');
        }
        return parent::_objectToString($property, $params);
    }
}
