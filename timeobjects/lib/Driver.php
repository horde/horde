<?php
/**
 * @TODO
 */
abstract class TimeObjects_Driver
{
    protected $_params = array();

    /**
     *
     * @param array $params  The parameter array.
     */
    public function __construct(array $params)
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * @param $start
     * @param $end
     *
     * @return array  The array of time objects.
     */
    abstract public function listTimeObjects(Horde_Date $start = null, Horde_Date $end = null);

    /**
     * Ensure we have minimum requirements for concrete driver to run.
     *
     */
    abstract public function ensure();

    /**
     * Factory method
     *
     * @param $name
     * @param $params
     * @return unknown_type
     */
    public function factory($name, array $params = array())
    {
        $class = 'TimeObjects_Driver_' . basename($name);
        if (class_exists($class)) {
            return new $class($params);
        } else {
            throw new TimeObjects_Exception(sprintf('Unable to load the definition of %s'), $class);
        }
    }

}