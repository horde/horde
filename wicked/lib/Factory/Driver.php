<?php
/**
 * Wicked_Driver factory.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Wicked
 */
class Wicked_Factory_Driver extends Horde_Core_Factory_Injector
{
    /**
     * @var array
     */
    private $_instances = array();

    /**
     * Return an Wicked_Driver instance.
     *
     * @param Horde_Injector $injector  An injector object.
     *
     * @return Wicked_Driver  A driver instance.
     * @throws Wicked_Exception
     */
    public function create(Horde_Injector $injector)
    {
        $driver = Horde_String::ucfirst($GLOBALS['conf']['storage']['driver']);
        if (empty($driver)) {
            throw new Wicked_Exception('Wicked is not configured');
        }
        $signature = serialize(array($driver, $GLOBALS['conf']['storage']['params']['driverconfig']));
        if (empty($this->_instances[$signature])) {
            switch ($driver) {
            case 'Sql':
                $params = array('db' => $this->getDb($injector));
                break;
            }
            $class = 'Wicked_Driver_' . $driver;
            $this->_instances[$signature] = new $class($params);
        }

        return $this->_instances[$signature];
    }

    /**
     * Returns a Horde_Db instance for the SQL backend.
     *
     * @param Horde_Injector $injector  An injector object.
     *
     * @return Horde_Db_Adapter  A correctly configured Horde_Db_Adapter
     *                           instance.
     * @throws Wicked_Exception
     */
    public function getDb(Horde_Injector $injector)
    {
        try {
            if ($GLOBALS['conf']['storage']['params']['driverconfig'] == 'horde') {
                return $injector->getInstance('Horde_Db_Adapter');
            }
            return $injector->getInstance('Horde_Core_Factory_Db')
                ->create('wicked', 'storage');
        } catch (Horde_Exception $e) {
            throw new Wicked_Exception($e);
        }
    }
}
