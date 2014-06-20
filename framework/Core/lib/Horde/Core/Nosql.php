<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @copyright 2013-2014 Horde LLC
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */

/**
 * NoSQL administrative/utility tasks.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Core_NoSql
{
    /* getDrivers() mask constants. */
    const HAS_INDICES = 1;
    const NEEDS_INDICES = 2;

    /**
     * Retrieve the list of active NoSQL drivers for an application.
     *
     * @param string $app    Application name.
     * @param integer $mask  Filter drivers by this mask.
     *
     * @return array  List of NoSQL drivers.
     */
    public function getDrivers($app = 'horde', $mask = 0)
    {
        global $injector, $registry;

        try {
            $drivers = $registry->callAppMethod($app, 'nosqlDrivers');
        } catch (Horde_Exception $e) {
            return array();
        }

        /* Handle framework-level drivers here. */
        if ($app == 'horde') {
            $backends = array(
                'Horde_ActiveSync_State_Mongo' => function() use ($injector) {
                    return $injector->getInstance('Horde_ActiveSyncState');
                },
                'Horde_Cache_Storage_Mongo' => function() use ($injector) {
                    return $injector->getInstance('Horde_Core_Factory_Cache')->storage;
                },
                'Horde_History_Mongo' => function() use ($injector) {
                    return $injector->getInstance('Horde_History');
                },
                'Horde_Lock_Mongo' => function() use ($injector) {
                    return $injector->getInstance('Horde_Lock');
                },
                'Horde_Prefs_Storage_Mongo' => function() use ($injector) {
                    return $injector->getInstance('Horde_Core_Factory_Prefs')->storage;
                },
                'Horde_SessionHandler_Storage_Mongo' => function() use ($injector) {
                    return $injector->getInstance('Horde_Core_Factory_SessionHandler')->storage;
                },
                'Horde_Token_Mongo' => function() use ($injector) {
                    return $injector->getInstance('Horde_Token');
                },
                'Horde_Vfs_Mongo' => function() use ($injector) {
                    return $injector->getInstance('Horde_Core_Factory_Vfs')->create();
                },
            );

            foreach ($backends as $key => $func) {
                try {
                    $val = $func();
                    if ($val instanceof $key) {
                        $drivers[] = $val;
                    }
                } catch (Horde_Exception $e) {
                }
            }
        }

        if ($mask & self::NEEDS_INDICES) {
            $mask |= self::HAS_INDICES;
        }

        foreach ($drivers as $key => $val) {
            if ($mask & self::HAS_INDICES) {
                if ($val instanceof Horde_Mongo_Collection_Index) {
                    if (($mask & self::NEEDS_INDICES) &&
                        $val->checkMongoIndices()) {
                        unset($drivers[$key]);
                    }
                } else {
                    unset($drivers[$key]);
                }
            }
        }

        return $drivers;
    }

    /**
     * Make sure indices are built for all NoSQL drivers in the given
     * application.
     *
     * @param string $app  Application name.
     */
    public function buildIndices($app = 'horde')
    {
        foreach ($this->getDrivers($app, self::HAS_INDICES) as $val) {
            if ($val instanceof Horde_Mongo_Collection_Index) {
                $val->createMongoIndices();
            }
        }
    }

}
