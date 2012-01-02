<?php
/**
 * Skeleton_Driver factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Your Name <you@example.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Skeleton
 */
class Skeleton_Factory_Driver extends Horde_Core_Factory_Injector
{
    /**
     * @var array
     */
    private $_instances = array();

    /**
     * Return an Skeleton_Driver instance.
     *
     * @return Skeleton_Driver
     */
    public function create(Horde_Injector $injector)
    {
        $driver = Horde_String::ucfirst($GLOBALS['conf']['storage']['driver']);
        $signature = serialize(array($driver, $GLOBALS['conf']['storage']['params']['driverconfig']));
        if (empty($this->_instances[$signature])) {
            switch ($driver) {
            case 'Sql':
                try {
                    if ($GLOBALS['conf']['storage']['params']['driverconfig'] == 'horde') {
                        $db = $injector->getInstance('Horde_Db_Adapter');
                    } else {
                        $db = $injector->getInstance('Horde_Core_Factory_Db')
                            ->create('skeleton', 'storage');
                    }
                } catch (Horde_Exception $e) {
                    throw new Skeleton_Exception($e);
                }
                $params = array('db' => $db);
                break;
            case 'Ldap':
                try {
                    $params = array('ldap' => $injector->getIntance('Horde_Core_Factory_Ldap')->create('skeleton', 'storage'));
                } catch (Horde_Exception $e) {
                    throw new Skeleton_Exception($e);
                }
                break;
            }
            $class = 'Skeleton_Driver_' . $driver;
            $this->_instances[$signature] = new $class($params);
        }

        return $this->_instances[$signature];
    }
}
