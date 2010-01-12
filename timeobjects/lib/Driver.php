<?php
/**
 * @TODO
 */
class TimeObjects_Driver
{
    protected $_params = array();

    public function __construct($params)
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * @abstract
     * @param $start
     * @param $end
     * @return unknown_type
     */
    public function listTimeObjects($start, $end){}

    /**
     * Ensure we have minimum requirements for concrete driver to run.
     *
     * @abstract
     */
    function ensure(){}

    /**
     * Factory method
     *
     * @param $name
     * @param $params
     * @return unknown_type
     */
    public function factory($name, $params = array())
    {
        $class = 'TimeObjects_Driver_' . basename($name);
        if (class_exists($class)) {
            $driver = new $class($params);
        } else {
            $driver = PEAR::raiseError(sprintf(_("Unable to load the definition of %s."), $class));
        }

        return $driver;
    }
}