<?php

class Horde_Icalendar_Writer_Vcalendar_20 extends Horde_Icalendar_Writer_Base
{
    protected $_propertyMap = array('product' => 'PRODID',
                                    'start' => 'DTSTART',
                                    'stamp' => 'DTSTAMP');

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
