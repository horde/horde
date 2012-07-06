<?php
/**
 * A Horde_Injector based Horde_Vfs factory.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */

/**
 * A Horde_Injector based Horde_Vfs factory.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */
class Gollem_Factory_Vfs extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Returns the VFS instance.
     *
     * @param string $backend  The backend to return.
     *
     * @return Horde_Vfs  The VFS object.
     */
    public function create($backend)
    {
        if (empty($this->_instances[$backend])) {
            $be_config = Gollem_Auth::getBackend($backend);
            $params = $be_config['params'];

            if (!empty($params['password'])) {
                $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
                $params['password'] = $secret->read($secret->getKey(), $params['password']);
            }

            switch (Horde_String::lower($be_config['driver'])) {
            case 'sql':
            case 'sqlfile':
            case 'musql':
                $db_params = $params;
                unset($db_params['table']);
                $params['db'] = $this->_injector
                    ->getInstance('Horde_Core_Factory_Db')
                    ->create('gollem', $db_params);
                $params['user'] = $GLOBALS['registry']->getAuth();
                break;
            }

            $this->_instances[$backend] = Horde_Vfs::factory($be_config['driver'], $params);
        }

        return $this->_instances[$backend];
    }
}
