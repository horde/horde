<?php
/**
 * Factory for TimeObjects_Driver
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @package  Ansel
 */
class TimeObjects_Factory_Driver
{
    /**
     * Creates a concrete TimeObjects_Driver object.
     *
     * @param string $name   The driver type to create.
     * @param array $params  Any driver parameters.
     *
     * @return TimeObjects_Driver
     * @throws TimeObjects_Exception
     */
    public function create($name, array $params = array())
    {
        $class = 'TimeObjects_Driver_' . basename($name);
        if (class_exists($class)) {
            return new $class($params);
        } else {
            throw new TimeObjects_Exception(sprintf('Unable to load the definition of %s'), $class);
        }
    }

}