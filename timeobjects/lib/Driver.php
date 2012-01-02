<?php
/**
 * Base TimeObjects_Driver.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package TimeObjects
 */
abstract class TimeObjects_Driver
{
    protected $_params = array();

    /**
     * Constructor
     *
     * @param array $params  The parameter array.
     */
    public function __construct(array $params)
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Get a list of TimeObjects.
     *
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
     *
     * @return TimeObjects_Driver
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