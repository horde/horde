<?php
/**
 * A Horde_Injector:: based Ingo_Storage:: factory.
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @link     http://pear.horde.org/index.php?package=Ingo
 * @package  Ingo
 */

/**
 * A Horde_Injector:: based Ingo_Storage:: factory.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @link     http://pear.horde.org/index.php?package=Ingo
 * @package  Ingo
 */
class Ingo_Factory_Storage extends Horde_Core_Factory_Base
{
    /**
     * Singleton instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the Ingo_Storage instance.
     *
     * @param string $driver  Driver name.
     * @param array $params   Configuration parameters.
     *
     * @return Ingo_Storage  The singleton instance.
     *
     * @throws Ingo_Exception
     */
    public function create($driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }
        $driver = ucfirst(basename($driver));

        if (!isset($this->_instances[$driver])) {
            if (is_null($params)) {
                $params = Horde::getDriverConfig('storage', $driver);
            }

            switch ($driver) {
            case 'Sql':
                $params['db'] = $GLOBALS['injector']->getInstance('Horde_Db_Adapter');
                $params['table_forwards'] = 'ingo_forwards';
                $params['table_lists'] = 'ingo_lists';
                $params['table_rules'] = 'ingo_rules';
                $params['table_spam'] = 'ingo_spam';
                $params['table_vacations'] = 'ingo_vacations';
                break;
            }

            $class = 'Ingo_Storage_' . $driver;
            if (class_exists($class)) {
                $this->_instances[$driver] = new $class($params);
            } else {
                throw new Ingo_Exception(sprintf(_("Unable to load the storage driver \"%s.\""), $class));
            }
        }

        return $this->_instances[$driver];
    }

}
