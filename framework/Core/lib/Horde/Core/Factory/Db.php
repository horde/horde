<?php
/**
 * A Horde_Injector:: based factory for creating Horde_Db_Adapter objects.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based factory for creating Horde_Db_Adapter objects.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Db extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Returns the DB instance.
     *
     * @param string $app            The application.
     * @param string|array $backend  The backend, see Horde::getDriverConfig().
     *                               If this is an array, this is used as the
     *                               configuration array.
     *
     * @return Horde_Db_Adapter  The singleton instance.
     * @throws Horde_Exception
     * @throws Horde_Db_Exception
     */
    public function create($app = 'horde', $backend = null)
    {
        $sig = hash('md5', serialize(array($app, $backend)));

        if (isset($this->_instances[$sig])) {
            return $this->_instances[$sig];
        }

        $pushed = ($app == 'horde')
            ? false
            : $GLOBALS['registry']->pushApp($app);

        $config = is_array($backend)
            ? $backend
            : $this->getConfig($backend);

        /* Determine if we are using the base SQL config. */
        if (isset($config['driverconfig']) &&
            ($config['driverconfig'] == 'horde')) {
            $this->_instances[$sig] = $this->create();
            return $this->_instances[$sig];
        }

        // Prevent DSN from getting polluted (this only applies to
        // non-custom auth type connections. All other custom sql
        // configurations MUST be cleansed prior to passing to the
        // factory (at least until Horde 5).
        if (!is_array($backend) && $backend == 'auth') {
            unset($config['driverconfig'],
                  $config['query_auth'],
                  $config['query_add'],
                  $config['query_getpw'],
                  $config['query_update'],
                  $config['query_resetpassword'],
                  $config['query_remove'],
                  $config['query_list'],
                  $config['query_exists'],
                  $config['encryption'],
                  $config['show_encryption'],
                  $config['username_field'],
                  $config['password_field'],
                  $config['table'],
                  $config['login_block'],
                  $config['login_block_count'],
                  $config['login_block_time']);
        }
        unset($config['umask']);

        $e = null;
        try {
            $this->_instances[$sig] = $this->createDb($config);
        } catch (Horde_Exception $e) {}

        if ($pushed) {
            $GLOBALS['registry']->popApp();
        }

        if ($e) {
            throw $e;
        }

        return $this->_instances[$sig];
    }

    /**
     */
    public function getConfig($backend)
    {
        return Horde::getDriverConfig($backend, 'sql');
    }

    /**
     */
    public function createDb($config)
    {
        // Split read?
        if (!empty($config['splitread'])) {
            $read_config = $config['read'];
            unset($config['read'], $config['splitread']);
            return new Horde_Db_Adapter_SplitRead($this->createDb(array_merge($config, $read_config)), $this->createDb($config));
        }

        if (!isset($config['adapter'])) {
            if (empty($config['phptype'])) {
                throw new Horde_Exception('The database configuration is missing.');
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
            unset($config['hostspec']);
        }

        $adapter = str_replace(' ', '_', ucwords(str_replace('_', ' ', basename($config['adapter']))));
        $class = $this->_getDriverName($adapter, 'Horde_Db_Adapter');

        $ob = new $class($config);

        if (!isset($config['cache'])) {
            $injector = $this->_injector->createChildInjector();
            $injector->setInstance('Horde_Db_Adapter', $ob);
            $cacheFactory = $this->_injector->getInstance('Horde_Core_Factory_Cache');
            $cache = $cacheFactory->create($injector);
            $ob->setCache($cache);
        }

        if (!isset($config['logger'])) {
            $ob->setLogger($this->_injector->getInstance('Horde_Log_Logger'));
        }

        return $ob;
    }
}
