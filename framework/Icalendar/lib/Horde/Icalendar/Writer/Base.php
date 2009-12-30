<?php

abstract class Horde_Icalendar_Writer_Base
{
    protected $_propertyMap = array();

    protected $_output = '';

    public function export($object)
    {
        $this->_output = '';
        $this->_exportComponent($object);
        return $this->_output;
    }

    protected function _exportComponent($object)
    {
        $basename = Horde_String::upper(str_replace('Horde_Icalendar_', '', get_class($object)));
        $this->_output .= 'BEGIN:' . $basename . "\n";
        foreach ($object as $name => $property) {
            $this->_exportProperty($name, $property);
        }
        foreach ($object->components as $component) {
            $this->_exportComponent($component);
        }
        $this->_output .= 'END:' . $basename . "\n";
        return $this->_output;
    }

    protected function _exportProperty($name, $property)
    {
        if (isset($property['values'])) {
            if (isset($this->_propertyMap[$name])) {
                $name = $this->_propertyMap[$name];
            }
            foreach ($property['values'] as $value) {
                $this->_output .= Horde_String::upper($name) . ':' . $value . "\n";
            }
        }
    }

}
