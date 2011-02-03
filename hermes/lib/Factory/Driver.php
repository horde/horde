<?php
/**
 * Hermes_Driver:: factory
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Hermes
 */
class Hermes_Factory_Driver extends Horde_Core_Factory_Base
{
    /**
     * @var array
     */
    private $_instances = array();

    /**
     * Return an Hermes_Storage instance.
     *
     * @return Ansel_Storage
     */
    public function create()
    {
        $driver = $GLOBALS['conf']['storage']['driver'];
        $signature = serialize(array($driver, $GLOBALS['conf']['storage']['params']['driverconfig']));
        if (empty($this->_instances[$signature])) {
            if ($driver == 'sql' && $GLOBALS['conf']['storage']['params']['driverconfig'] == 'horde') {
                $params = array('db_adapter' => $this->_injector->getInstance('Horde_Db_Adapter'));
            } else {
                throw new Horde_Exception('Using non-global db connection not yet supported.');
            }
            $class = 'Hermes_Driver_' . Horde_String::ucfirst($driver);
            $this->_instances[$signature] = new $class($params);
        }

        return $this->_instances[$signature];
    }

}
