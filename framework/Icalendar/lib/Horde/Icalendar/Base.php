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
 * This is the base class for any VCALENDAR, VCARD, VEVENT etc. component.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Icalendar
 */
abstract class Horde_Icalendar_Base implements Iterator
{
    /**
     * Subcomponents of this component, e.g. VEVENT and VTODO components inside
     * a VCALENDAR component.
     *
     * @var array
     */
    public $components = array();

    /**
     * The component properties.
     *
     * This list is populated in the constructor of the sub-classes and
     * contains all valid properties and property rules for this component
     * type. The list keys are the property names and the values are hashes
     * with the following keys:
     * - 'required' (boolean): Whether this is a mandatory property.
     * - 'multiple' (boolean): Whether this property can appear multiple times.
     * - 'type' (string): The scalar type of this property (must have a matching
     *                    is_*() function.
     * - 'class' (string): The object type of this property.
     * - 'values' (array): The property values.
     * - 'params' (array): The property parameters.
     *
     * @var array
     */
    protected $_properties = array();

    /**
     * Constructor.
     *
     * @param array $properties  A hash of properties and values to populate
     *                           this object with.
     *
     * @throws InvalidArgumentException
     * @throws Horde_Icalendar_Exception
     */
    public function __construct(array $properties = array())
    {
        foreach ($properties as $property => $value) {
            $this->addProperty($property, $value);
        }
    }

    /**
     * Validates a property-value-pair.
     *
     * Values and parameters might be manipulated by this method.
     *
     * @param string $property  A property name.
     * @param mixed $value      A property value.
     * @param array $params     Property parameters.
     *
     * @throws InvalidArgumentException
     */
    protected function _validate($property, &$value, array &$params = array())
    {
        if (!isset($this->_properties[$property])) {
            throw new InvalidArgumentException($property . ' is not a valid property');
        }
        $myProperty = $this->_properties[$property];
        if (isset($myProperty['type'])) {
            $func = 'is_' . $myProperty['type'];
            if (!$func) {
                throw new InvalidArgumentException($value . ' is not a ' . $myProperty['type']);
            }
        } elseif (isset($myProperty['class'])) {
            if (!($value instanceof $myProperty['class'])) {
                throw new InvalidArgumentException($value . ' is not of class ' . $myProperty['class']);
            }
        }
        if ($property == 'stamp') {
            $value->setTimezone('UTC');
        }
    }

    /**
     * Setter for quickly setting properties without property parameters.
     *
     * @param string $property  A property name.
     * @param mixed $value      A property value.
     * 
     * @throws InvalidArgumentException
     */
    public function __set($property, $value)
    {
        $this->_validate($property, $value);
        $this->_setProperty($property, $value);
    }

    /**
     * Sets the value of a property.
     *
     * @param string $property  A property name.
     * @param mixed $value      A property value.
     * @param array $params     Property parameters.
     *
     * @throws InvalidArgumentException
     */
    public function setProperty($property, $value, array $params = array())
    {
        $this->_validate($property, $value, $params);
        $this->_setProperty($property, $value, $params);
    }

    /**
     * Adds the value of a property.
     *
     * @param string $property  A property name.
     * @param mixed $value      A property value.
     * @param array $params     Property parameters.
     *
     * @throws InvalidArgumentException
     * @throws Horde_Icalendar_Exception
     */
    public function addProperty($property, $value, array $params = array())
    {
        $this->_validate($property, $value, $params);
        if (!$this->_properties[$property]['multiple'] &&
            !empty($this->_properties[$property]['values'])) {
            throw new Horde_Icalendar_Exception($property . ' properties must not occur more than once.');
        }
        $this->_setProperty($property, $value, $params, true);
    }

    /**
     * Sets the value of a property.
     *
     * @param string $property  A property name.
     * @param mixed $value      A property value.
     * @param array $params     Property parameters.
     * @param boolean $add      Whether to add (instead of replace) the value.
     */
    protected function _setProperty($property, $value, array $params = array(),
                                    $add = false)
    {
        if ($add) {
            if (!isset($this->_properties[$property]['values'])) {
                $this->_properties[$property]['values'] = array();
                $this->_properties[$property]['params'] = array();
            }
            $this->_properties[$property]['values'][] = $value;
            $this->_properties[$property]['params'][] = $params;
        } else {
            $this->_properties[$property]['values'] = array($value);
            $this->_properties[$property]['params'] = array($params);
        }
    }

    /**
     * Returns the value(s) of a property.
     *
     * @param string $property  A property name.
     *
     * @return mixed  The property value, or an array of values if the property
     *                is allowed to have multiple values.
     *
     * @throws InvalidArgumentException
     */
    public function __get($property)
    {
        if (!isset($this->_properties[$property])) {
            throw new InvalidArgumentException($property . ' is not a valid property');
        }
        return isset($this->_properties[$property]['values'])
            ? ($this->_properties[$property]['multiple']
               ? $this->_properties[$property]['values']
               : $this->_properties[$property]['values'][0])
            : null;
    }

    /**
     * Returns the parameters of a property.
     *
     * @param string $property  A property name.
     *
     * @return array  The parameters for the property.
     * @throws Horde_Icalendar_Exception
     */
    function getParameters($name)
    {
        if (!isset($this->_properties[$property])) {
            throw new InvalidArgumentException($property . ' is not a valid property');
        }
        return $this->_properties[$property]['params'];
    }

    /**
     * Validates the complete component, checking for missing properties or
     * invalid property combinations.
     *
     * @throws Horde_Icalendar_Exception
     */
    public function validate()
    {
        foreach ($this->_properties as $name => $property) {
            if (!empty($property['required']) && !isset($property['values'])) {
                switch ($name) {
                case 'uid':
                    $this->uid = (string)new Horde_Support_Guid;
                    break;
                case 'stamp':
                    $this->stamp = new Horde_Date(time());
                    break;
                default:
                    // @todo Use LSB (static::__CLASS__) once we require PHP 5.3
                    $component = Horde_String::upper(str_replace('Horde_Icalendar_', '', get_class($this)));
                    throw new Horde_Icalendar_Exception('This ' . $component . ' component must have a ' . $name . ' property set');
                }
            }
        }
    }

    /**
     * Returns the current property information for the Iterator interface.
     *
     * @return array  A hash with property information.
     */
    public function current()
    {
        return current($this->_properties);
    }

    /**
     * Returns the current property name for the Iterator interface.
     *
     * @return string  A property name.
     */
    public function key()
    {
        return key($this->_properties);
    }

    /**
     * Advances to the current property for the Iterator interface.
     */
    public function next()
    {
        next($this->_properties);
    }

    /**
     * Rewinds to the first property for the Iterator interface.
     */
    public function rewind()
    {
        reset($this->_properties);
    }

    /**
     * Returns whether there still more properties for the Iterator interface.
     *
     * @return boolean  True if there are more properties to iterate.
     */
    public function valid()
    {
        return current($this->_properties) !== false;
    }

    /**
     * Exports this component into a string.
     *
     * @todo Use LSB (static::__CLASS__) once we require PHP 5.3
     *
     * @return string  The string representation of this component.
     * @throws Horde_Icalendar_Exception
     */
    public function export()
    {
        $this->validate();

        $format = str_replace('Horde_Icalendar_', '', get_class($this));
        $version = str_replace('.', '', $this->version);
        $class = 'Horde_Icalendar_Writer_' . $format . '_' . $version;
        if (!class_exists($class)) {
            throw new Horde_Icalendar_Exception($class . ' not found.');
        }

        $writer = new $class($params);
        return $writer->export($this);
    }
}
