<?php
/**
 * Factory for TimeObjects_Driver
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Timeobjects
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

        switch ($class) {
        case 'TimeObjects_Driver_Weather':
            if (!class_exists('Horde_Service_Weather')) {
                throw new TimeObjects_Exception('Horde_Services_Weather is not installed');
            }
            break;
        case 'TimeObjects_Driver_FacebookEvents':
            if (!class_exists('Horde_Service_Facebook')) {
                throw new TimeObjects_Exception('Horde_Services_Facebook is not installed');
            }
            break;
        default:
            throw new TimeObjects_Exception(sprintf('Unable to load the definition of %s', $class));
        }

        return new $class($params);
    }
}