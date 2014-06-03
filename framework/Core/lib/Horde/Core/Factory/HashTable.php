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
 * A Horde_Injector based factory for creating a HashTable object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Core
 * @package   Core
 */
class Horde_Core_Factory_HashTable extends Horde_Core_Factory_Injector
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        global $conf;

        $logger = $injector->getInstance('Horde_Core_Log_Wrapper');

        // DEPRECATED: BC config
        if (!empty($conf['memcache']['enabled'])) {
            return new Horde_HashTable_Memcache(array(
                'logger' => $logger,
                'memcache' => $injector->getInstance('Horde_Memcache')
            ));
        }

        $driver = empty($conf['hashtable']['driver'])
            ? 'memory'
            : $conf['hashtable']['driver'];
        $lc_driver = Horde_String::lower($driver);

        $params = Horde::getDriverConfig('hashtable', $driver);

        switch ($lc_driver) {
        case 'memcache':
            return new Horde_HashTable_Memcache(array(
                'logger' => $logger,
                'memcache' => new Horde_Memcache(array_merge($params, array(
                    'logger' => $logger
                )))
            ));

        case 'predis':
            $params = array_merge(array(
                'hostspec' => array(),
                'password' => null,
                'port' => '',
                'protocol' => 'tcp',
            ), $params);
            $redis_params = array();

            $common = array_filter(array(
                'password' => strlen($params['password']) ? $params['password'] : null,
                'persistent' => !empty($params['persistent'])
            ));

            switch ($params['protocol']) {
            case 'tcp':
                foreach ($params['hostspec'] as $key => $val) {
                    $redis_params[] = array_merge($common, array_filter(array(
                        'host' => trim($val),
                        'port' => isset($params['port']) ? trim($params['port']) : null,
                        'scheme' => 'tcp'
                    )));
                }
                break;

            case 'unix':
                $redis_params[] = array_merge($common, array(
                    'path' => trim($params['socket']),
                    'scheme' => 'unix'
                ));
                break;
            }

            /* No need to use complex clustering if not needed. */
            if (count($redis_params) === 1) {
                $redis_params = reset($redis_params);
            }

            return new Horde_HashTable_Predis(array(
                'logger' => $logger,
                'predis' => new Predis\Client($redis_params)
            ));

        case 'memory':
        default:
            return new Horde_HashTable_Memory(array(
                'logger' => $logger
            ));
        }
    }

}
