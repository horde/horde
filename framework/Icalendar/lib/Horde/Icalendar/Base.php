<?php

abstract class Horde_Icalendar_Base implements Iterator
{
    /**
     * @var array
     */
    public $components = array();

    /**
     * @var array
     */
    protected $_properties = array();

    public function __construct($properties = array())
    {
        foreach ($properties as $property => $value) {
            $this->addProperty($property, $value);
        }
    }

    /**
     * Validates a property-value-pair.
     *
     * @throws InvalidArgumentException
     */
    protected function _validate($property, &$value, &$params = array())
    {
        if (!isset($this->_properties[$property])) {
            throw new InvalidArgumentException($property . ' is not a valid property');
        }
        $myProperty = &$this->_properties[$property];
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
     * Setter.
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
     * @param string $property  The name of the property.
     * @param string $value     The value of the property.
     * @param array $params     Array containing any addition parameters for
     *                          this property.
     *
     * @throws InvalidArgumentException
     */
    public function setProperty($property, $value, $params = array())
    {
        $this->_validate($property, $value);
        $this->_setProperty($property, $value, $params);
    }

    /**
     * Adds the value of a property.
     *
     * @param string $property  The name of the property.
     * @param string $value     The value of the property.
     * @param array $params     Array containing any addition parameters for
     *                          this property.
     *
     * @throws InvalidArgumentException
     * @throws Horde_Icalendar_Exception
     */
    public function addProperty($property, $value, $params = array())
    {
        $this->_validate($property, $value);
        if (!$this->_properties[$property]['multiple'] &&
            !empty($this->_properties[$property]['values'])) {
            throw new Horde_Icalendar_Exception($property . ' properties must not occur more than once.');
        }
        $this->_setProperty($property, $value, $params, true);
    }

    /**
     * Sets the value of a property.
     *
     * @param string $property  The name of the property.
     * @param string $value     The value of the property.
     * @param array $params     Array containing any addition parameters for
     *                          this property.
     * @param boolean $add      Whether to add (instead of replace) the value.
     *
     * @throws InvalidArgumentException
     */
    protected function _setProperty($property, $value, $params = array(), $add = false)
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
            $this->_properties[$property]['params'] = $params;
        }
    }

    /**
     * Getter.
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
     * Returns the value of an property.
     *
     * @param string $name     The name of the property.
     * @param boolean $params  Return the parameters for this property instead
     *                         of its value.
     *
     * @return mixed (object)  PEAR_Error if the property does not exist.
     *               (string)  The value of the property.
     *               (array)   The parameters for the property or
     *                         multiple values for an property.
     */
    function getProperty($name, $params = false)
    {
        $result = array();
        foreach ($this->_properties as $property) {
            if ($property['name'] == $name) {
                if ($params) {
                    $result[] = $property['params'];
                } else {
                    $result[] = $property['value'];
                }
            }
        }
        if (!count($result)) {
            require_once 'PEAR.php';
            return PEAR::raiseError('Property "' . $name . '" Not Found');
        } if (count($result) == 1 && !$params) {
            return $result[0];
        } else {
            return $result;
        }
    }

    public function getProperties()
    {
        return $this->_properties;
    }

    /**
     * Validates the complete component for missing properties or invalid
     * property combinations.
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

    public function current()
    {
        return current($this->_properties);
    }

    public function key()
    {
        return key($this->_properties);
    }

    public function next()
    {
        next($this->_properties);
    }

    public function rewind()
    {
        reset($this->_properties);
    }

    public function valid()
    {
        return current($this->_properties) !== false;
    }

    /**
     * @todo Use LSB (static::__CLASS__) once we require PHP 5.3
     */
    public function export()
    {
        $this->validate();
        $writer = Horde_Icalendar_Writer::factory(
            str_replace('Horde_Icalendar_', '', get_class($this)),
            str_replace('.', '', $this->version));
        return $writer->export($this);
    }

}
