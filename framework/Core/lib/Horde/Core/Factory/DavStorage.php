<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Core
 */

/**
 * Factory for the DAV metadata storage.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Core
 */
class Horde_Core_Factory_DavStorage extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        $driver = $GLOBALS['conf']['davstorage']['driver'];
        $params = Horde::getDriverConfig('davstorage', $driver);

        switch ($driver) {
        case 'Sql':
            $class = 'Horde_Dav_Storage_Sql';
            $params['db'] = $injector
                ->getInstance('Horde_Core_Factory_Db')
                ->create('horde', 'davstorage');
            break;

        default:
            throw new Horde_Exception('A storage backend for DAV has not been configured.');
            break;
        }

        return new $class($params);
    }
}
