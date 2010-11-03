<?php
/**
 * A Horde_Injector:: based factory for creating Horde_Db_Adapter objects.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based factory for creating Horde_Db_Adapter objects.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Db
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return the DB instance.
     *
     * @param string $app  The application.
     * @param mixed $type  The type. If this is an array, this is used as
     *                     the configuration array.
     *
     * @return Horde_Db_Adapter  The singleton instance.
     * @throws Horde_Exception
     * @throws Horde_Db_Exception
     */
    public function create($app = 'horde', $type = null)
    {
        $sig = hash('md5', serialize($app . '|' . $type));

        if (isset($this->_instances[$sig])) {
            return $this->_instances[$sig];
        }

        $pushed = ($app == 'horde')
            ? false
            : $GLOBALS['registry']->pushApp($app);

        $config = is_array($type)
            ? $type
            : $this->getConfig($type);

        /* Determine if we are using the base SQL config. */
        if (isset($config['driverconfig']) &&
            ($config['driverconfig'] == 'horde')) {
            $this->_instances[$sig] = $this->create();
            return $this->_instances[$sig];
        }

        // Prevent DSN from getting polluted
        if (!is_array($type) && $type == 'auth') {
            unset($config['query_auth'],
                  $config['query_add'],
                  $config['query_getpw'],
                  $config['query_update'],
                  $config['query_resetpassword'],
                  $config['query_remove'],
                  $config['query_list'],
                  $config['query_exists'],
                  $config['encryption'],
                  $config['show_encryption']);
        }
        unset($config['umask']);

        try {
            $this->_instances[$sig] = $this->_createDb($config);
        } catch (Horde_Exception $e) {
            if ($pushed) {
                $GLOBALS['registry']->popApp();
            }
            throw $e;
        }

        if ($pushed) {
            $GLOBALS['registry']->popApp();
        }

        return $this->_instances[$sig];
    }

    /**
     */
    public function getConfig($type)
    {
        return Horde::getDriverConfig($type, 'sql');
    }

    /**
     */
    protected function _createDb($config)
    {
        // Split read?
        if (!empty($config['splitread'])) {
            unset($config['splitread']);
            $write_db = $this->_createDb($config);
            $config = array_merge($config, $config['read']);
            $read_db = $this->_createDb($config);
            return new Horde_Db_Adapter_SplitRead($read_db, $write_db);
        }

        if (!isset($config['adapter'])) {
            if ($config['phptype'] == 'oci8') {
                $config['phptype'] = 'oci';
            }
            if ($config['phptype'] == 'mysqli') {
                $config['adapter'] = 'mysqli';
            } elseif ($config['phptype'] == 'mysql') {
                if (extension_loaded('pdo_mysql')) {
                    $config['adapter'] = 'pdo_mysql';
                } else {
                    $config['adapter'] = 'mysql';
                }
            } else {
                $config['adapter'] = 'pdo_' . $config['phptype'];
            }
        }

        if (!empty($config['hostspec'])) {
            $config['host'] = $config['hostspec'];
        }

        $adapter = str_replace(' ', '_' , ucwords(str_replace('_', ' ', basename($config['adapter']))));
        $class = 'Horde_Db_Adapter_' . $adapter;

        if (class_exists($class)) {
            unset($config['hostspec'], $config['splitread']);
            $ob = new $class($config);

            if (!isset($config['cache'])) {
                $ob->setCache($this->_injector->getInstance('Horde_Cache'));
            }

            if (!isset($config['logger'])) {
                $ob->setLogger($this->_injector->getInstance('Horde_Log_Logger'));
            }

            return $ob;
        }

        throw new Horde_Exception('Adapter class "' . $class . '" not found');
    }
}
