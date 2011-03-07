<?php
/**
 * This set of classes implements a Flyweight pattern
 * (http://en.wikipedia.org/wiki/Flyweight_pattern). Refactor/rename
 * some based on this fact?
 *
 * @package Lens
 */

/**
 * @package Lens
 */
class Horde_Lens implements Horde_Lens_Interface {

    /**
     */
    protected $_target;

    /**
     */
    public function decorate($target)
    {
        $this->_target = $target;
        return $this;
    }

    /**
     */
    public function __get($key)
    {
        return $this->_target->$key;
    }

    /**
     */
    public function __set($key, $value)
    {
        $this->_target->$key = $value;
        return $this;
    }

    /**
     */
    public function __call($func, $params)
    {
        return call_user_func_array(array($this->_target, $func), $params);
    }

}
