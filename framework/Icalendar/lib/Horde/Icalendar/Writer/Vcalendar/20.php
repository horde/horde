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
     * Exports an individual property into a string.
     *
     * @param string $name     A property name.
     * @param array $property  A property hash.
     */
    protected function _exportProperty($name, $property)
    {
        if (!isset($property['values'])) {
            return;
        }
        if (isset($property['class']) && $property['class'] == 'Horde_Date') {
            if (isset($this->_propertyMap[$name])) {
                $name = $this->_propertyMap[$name];
            }
            foreach ($property['values'] as $value) {
                $this->_output .= Horde_String::upper($name) . ':' . $value->format('Ymd\THms\Z') . "\n";
            }
        } else {
            parent::_exportProperty($name, $property);
        }
    }

}
