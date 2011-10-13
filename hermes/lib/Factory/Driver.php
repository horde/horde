<?php
/**
 * Hermes_Driver:: factory
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Hermes
 */
class Hermes_Factory_Driver extends Horde_Core_Factory_Injector
{
    /**
     * @var array
     */
    private $_instances = array();

    /**
     * Return an Hermes_Driver instance.
     *
     * @return Hermes_Driver
     */
    public function create(Horde_Injector $injector)
    {
        $driver = $GLOBALS['conf']['storage']['driver'];
        $signature = serialize(array($driver, $GLOBALS['conf']['storage']['params']['driverconfig']));
        if (empty($this->_instances[$signature])) {
            if ($driver == 'sql' && $GLOBALS['conf']['storage']['params']['driverconfig'] == 'horde') {
                $params = array('db_adapter' => $injector->getInstance('Horde_Db_Adapter'));
            } else {
                throw new Horde_Exception('Using non-global db connection not yet supported.');
            }
            $class = 'Hermes_Driver_' . Horde_String::ucfirst($driver);
            $this->_instances[$signature] = new $class($params);
        }

        return $this->_instances[$signature];
    }

}
