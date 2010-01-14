<?php
class Horde_Core_Binder_Db implements Horde_Injector_Binder
{
    /**
     * If there are multiple db instances, which one should be returned?
     * @var string
     */
    protected $_kind = 'manager';

    /**
     * @param string $kind  The kind of database connection to return - reader, writer, manager.
     */
    public function __construct($kind = null)
    {
        $this->_kind = $kind;
    }

    /**
     * Handle Horde-style configuration arrays, PEAR DB/MDB2 arrays or DSNs, or
     * PDO DSNS.
     */
    public function create(Horde_Injector $injector)
    {
        $config = $GLOBALS['conf']['sql'];
        if (!isset($config['adapter'])) {
            $config['adapter'] = $config['phptype'] == 'mysqli' ? 'mysqli' : 'pdo_' . $config['phptype'];
        }
        if (!empty($config['hostspec'])) {
            $config['host'] = $config['hostspec'];
        }

        if (!isset($config['logger'])) {
            $config['logger'] = $injector->getInstance('Horde_Log_Logger');
        }

        if (!isset($config['cache'])) {
            $config['cache'] = $injector->getInstance('Horde_Cache');
        }

        /* @TODO Based on $this->_kind and the splitread configuration, return a
         * reader, writer, or manager connection. */

        $adapter = str_replace(' ', '_' , ucwords(str_replace('_', ' ', basename($config['adapter']))));
        $class = 'Horde_Db_Adapter_' . $adapter;
        if (!class_exists($class)) {
            throw new Horde_Exception('Adapter class "' . $class . '" not found');
        }

        return new $class($config);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
