<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Core
 * @package   Core
 */

/**
 * A Horde_Injector based factory for creating Nosql objects.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Core
 * @package   Core
 */
class Horde_Core_Factory_Nosql extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Returns the instance.
     *
     * @param string $app            The application.
     * @param string|array $backend  The backend, see Horde::getDriverConfig().
     *                               If this is an array, this is used as the
     *                               configuration array.
     *
     * @return mixed  The singleton instance.
     * @throws Horde_Exception
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

        unset($config['umask']);

        $e = null;
        try {
            $this->_instances[$sig] = $this->createNosql($config);
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
        return Horde::getDriverConfig($backend, 'nosql');
    }

    /**
     * @throws Horde_Exception
     */
    public function createNosql($config)
    {
        if (empty($config['phptype'])) {
            throw new Horde_Exception('The database configuration is missing.');
        }

        switch ($config['phptype']) {
        case 'mongo':
            $ob = new Horde_Mongo_Client(empty($config['hostspec']) ? null : $config['hostspec']);
            if (isset($config['dbname']) && strlen($config['dbname'])) {
                $ob->dbname = $config['dbname'];
            }
            return $ob;

        default:
            throw new Horde_Exception(sprintf('Nosql driver %s doesn\'t exist.', $config['phptype']));
        }
    }

}
