<?php
/**
 * Horde_Form_Type Class
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @package Form
 */
abstract class Horde_Form_Type
{
    protected $_properties = array();

    /**
     * Type constructor. Takes a hash of key/value parameters.
     *
     * @param array $properties Any type properties to initialize.
     */
    public function __construct($properties = array())
    {
        $this->_properties = array();
        $vars = array_keys(get_object_vars($this));
        foreach ($vars as $var) {
            $this->_properties[] = substr($var, 1);
        }

        if ($this->_properties && $properties) {
            $properties = array_combine(array_slice($this->_properties, 0, count($properties)), $properties);
            foreach ($properties as $property => $value) {
                $this->__set($property, $value);
            }
        }
    }

    /**
     */
    abstract public function isValid($var, $vars, $value, &$message);

    /**
     */
    function getInfo($vars, $var, &$info)
    {
        $info = $var->getValue($vars);
    }

    /**
     */
    public function onSubmit()
    {
    }

    /**
     * To get the 'escape' property of a type:
     *   $escape = $type->escape;
     * If the property is not set this will return null.
     *
     * @param string $property The property to retrieve.
     */
    public function __get($property)
    {
        if (in_array($property, $this->_properties)) {
            $prop = '_' . $property;
            return $this->$prop;
        }

        return null;
    }

    /**
     * To set the 'escape' property of a type to true:
     *   $type->escape = true;
     *
     * @param string $property The property name to set.
     * @param mixed $value The property value.
     */
    public function __set($property, $value)
    {
        if (in_array($property, $this->_properties)) {
            $prop = '_' . $property;
            $this->$prop = $value;
        }
    }

    /**
     * To check if a type has a property named 'escape':
     *  if (isset($type->escape)) { ... }
     *
     * @param string $property Property name to check existance of.
     */
    public function __isset($property)
    {
        $prop = '_' . $property;
        return isset($this->$prop);
    }

    /**
     * To unset a Type property named 'escape':
     *   unset($type->escape);
     *
     * @param string $property Property name to unset.
     */
    public function __unset($property)
    {
        $prop = '_' . $property;
        unset($this->$prop);
    }

}
