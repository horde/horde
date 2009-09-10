<?php

abstract class Horde_Icalendar_Component_Base implements Iterator
{
    /**
     * @var array
     */
    protected $_properties = array();

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
        if ($this->_properties[$property]['multiple']) {
            $this->_properties[$property]['value'] = array($value);
            $this->_properties[$property]['params'] = array();
        } else {
            $this->_properties[$property]['value'] = $value;
            $this->_properties[$property]['params'] = null;
        }
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
        $this->$name = $value;
        $this->_properties[$property]['params'] = array($params);
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
            !isset($this->_properties[$property]['value'])) {
            throw new Horde_Icalendar_Exception($property . ' properties must not occur more than once.');
        }
        if (isset($this->_properties[$property]['value'])) {
            $this->_properties[$property]['value'][] = $value;
            $this->_properties[$property]['params'][] = $params;
        } else {
            $this->setProperty($property, $value, $params);
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
        return isset ($this->_properties[$property]['value'])
            ? $this->_properties[$property]['value']
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
            if (!empty($property['required']) && !isset($property['value'])) {
                switch ($name) {
                case 'uid':
                    $this->uid = (string)new Horde_Support_Guid;
                    break;
                case 'stamp':
                    $this->stamp = new Horde_Date(time());
                    break;
                default:
                    $component = Horde_String::upper(str_replace('Horde_Icalendar_Component_', '', get_class($this)));
                    throw new Horde_Icalendar_Exception($component . ' components must have a ' . $name . ' property set');
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

}
