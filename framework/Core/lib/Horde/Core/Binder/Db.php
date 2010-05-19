<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Db implements Horde_Injector_Binder
{
    /**
     * Handle Horde-style configuration arrays, PEAR DB/MDB2 arrays or DSNs, or
     * PDO DSNS.
     *
     * @return Horde_Db_Adapter_Base
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        return $this->_createDb($GLOBALS['conf']['sql'], $injector);
    }

    protected function _createDb($config, $injector)
    {
        if (!empty($config['splitread'])) {
            unset($config['splitread']);
            $config['write_db'] = $this->_createDb($config, $injector);
            $config = array_merge($config, $config['read']);
        }

        if (!isset($config['adapter'])) {
            if ($config['phptype'] == 'oci8') {
                $config['phptype'] = 'oci';
            }
            $config['adapter'] = ($config['phptype'] == 'mysqli')
                ? 'mysqli'
                : 'pdo_' . $config['phptype'];
        }

        if (!empty($config['hostspec'])) {
            $config['host'] = $config['hostspec'];
        }

        $adapter = str_replace(' ', '_' , ucwords(str_replace('_', ' ', basename($config['adapter']))));
        $class = 'Horde_Db_Adapter_' . $adapter;

        if (class_exists($class)) {
            $ob = new $class($config);

            if (!isset($config['cache'])) {
                $ob->setCache($injector->getInstance('Horde_Cache'));
            }

            if (!isset($config['logger'])) {
                $ob->setLogger($injector->getInstance('Horde_Log_Logger'));
            }

            return $ob;
        }

        throw new Horde_Exception('Adapter class "' . $class . '" not found');
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
