<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2008 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */

/**
 * Abstract parent class for Db Adapters.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter
{
    /**
     * Handle Horde-style configuration arrays, PEAR DB/MDB2 arrays or DSNs, or
     * PDO DSNS.
     */
    public static function getInstance($config)
    {
        $adapter = str_replace(' ', '_' , ucwords(str_replace('_', ' ', basename($config['adapter']))));
        $class = 'Horde_Db_Adapter_' . $adapter;
        if (!class_exists($class)) {
            throw new Horde_Db_Exception('Adapter class "' . $class . '" not found');
        }

        return new $class($config);
    }

}
