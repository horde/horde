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
 * This is the base class for any writers that export Horde_Icalendar objects
 * into strings.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Icalendar
 */
abstract class Horde_Icalendar_Writer_Base
{
    /**
     * A hash that maps property names of Horde_Icalendar objects to property
     * names defined in RFCs.
     *
     * @var array
     */
    protected $_propertyMap = array();

    /**
     * A buffer for the generated output.
     *
     * @var string
     */
    protected $_output = '';

    /**
     * A buffer to collect content for a single propery line.
     *
     * @var string
     */
    protected $_lineBuffer = '';

    /**
     * Exports a complete Horde_Icalendar object into a string.
     *
     * @param Horde_Icalendar_Base $object  An object to export.
     *
     * @return string  The string representation of the object.
     */
    public function export(Horde_Icalendar_Base $object)
    {
        $this->_output = '';
        $this->_exportComponent($object);
        return $this->_output;
    }

    /**
     * Exports an individual component into a string.
     *
     * @param Horde_Icalendar_Base $object  An component to export.
     */
    protected function _exportComponent(Horde_Icalendar_Base $object)
    {
        $basename = Horde_String::upper(str_replace('Horde_Icalendar_', '', get_class($object)));
        $this->_output .= 'BEGIN:' . $basename . "\r\n";
        foreach ($object as $name => $property) {
            $this->_exportProperty($name, $property);
        }
        foreach ($object->components as $component) {
            $this->_exportComponent($component);
        }
        $this->_output .= 'END:' . $basename . "\r\n";
    }

    /**
     * Exports an individual property into a string.
     *
     * @param string $name     A property name.
     * @param array $property  A property hash.
     */
    protected function _exportProperty($name, array $property)
    {
        if (isset($property['values'])) {
            if (isset($this->_propertyMap[$name])) {
                $name = $this->_propertyMap[$name];
            }
            foreach ($property['values'] as $num => $value) {
                $this->_lineBuffer = Horde_String::upper($name);
                foreach ($property['params'][$num] as $parameter => $pvalue) {
                    $this->_exportParameter($parameter, $pvalue);
                }
                if (is_object($value)) {
                    $value = $this->_objectToString($value, $property['params'][$num]);
                }
                $this->_addToLineBuffer(':' . $value);
                $this->_output .= $this->_lineBuffer . "\r\n";
            }
        }
    }

    /**
     * Exports an individual parameter into a string.
     *
     * @param string $name  A parameter name.
     * @param mixed $value  A parameter value.
     */
    protected function _exportParameter($name, $value)
    {
        $this->_addToLineBuffer(';' . Horde_String::upper($name));
        if (is_object($value)) {
            $value = $this->_objectToString($value, $property['params'][$num]);
        }
        $this->_addToLineBuffer('=' . Horde_String::upper($value));
    }

    /**
     * Adds some content to the internal line buffer.
     *
     * Sub-classes can extend this the method to apply line-folding techniques.
     *
     * @param string $content  Content to add to the line buffer.
     */
    protected function _addToLineBuffer($content)
    {
        $this->_lineBuffer .= $content;
    }

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
        return (string)$property;
    }
}
