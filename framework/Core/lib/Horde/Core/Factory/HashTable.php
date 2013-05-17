<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Core
 * @package   Core
 */

/**
 * A Horde_Injector based factory for creating a HashTable object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
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

        // DEPRECATED: BC config
        if (!empty($conf['memcache']['enabled'])) {
            return new Horde_HashTable_Memcache(array(
                'memcache' => $injector->getInstance('Horde_Memcache')
            ));
        }

        $driver = empty($conf['hashtable']['driver'])
            ? 'none'
            : $conf['hashtable']['driver'];
        $lc_driver = Horde_String::lower($driver);

        $params = Horde::getDriverConfig('hashtable', $driver);

        switch ($lc_driver) {
        case 'memcache':
            return new Horde_HashTable_Memcache(array(
                'memcache' => new Horde_Memcache(array_merge($params, array(
                    'logger' => $injector->getInstance('Horde_Log_Logger')
                )))
            ));

        case 'predis':
            $redis_params = array();
            if (isset($params['hostspec'])) {
                foreach (explode(',', $params['hostspec']) as $val) {
                    $redis_params[] = array_filter(array(
                        'host' => trim($val)
                    ));
                }

                if (isset($params['port'])) {
                    foreach (array_map('trim', explode(',', $params['port'])) as $key => $val) {
                        if ($val) {
                            $redis_params[$key]['port'] = $val;
                        }
                    }
                }
            }

            return new Horde_HashTable_Predis(array(
                'predis' => new Predis\Client($redis_params)
            ));

        case 'none':
        default:
            throw new Horde_Exception(Horde_Core_Translation::t("Attempting to use a distributed hash table without configuring a backend."));
        }
    }

}
