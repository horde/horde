<?php
/**
 * A Horde_Injector:: based Horde_Cache:: factory.
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
 * A Horde_Injector:: based Horde_Cache:: factory.
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
class Horde_Core_Factory_Cache
{
    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Singleton instances.
     *
     * @var array
     */
    private $_instances = array();

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
     * Return the Horde_Cache:: instance.
     *
     * @param array $opts  Options:
     * <pre>
     * 'session' - (boolean) Fallback to session driver, instead of null
     *             driver, if no cache config is found.
     *             DEFAULT: false
     * </pre>
     *
     * @return Horde_Cache_Base  The singleton instance.
     * @throws Horde_Cache_Exception
     */
    public function getCache(array $opts = array())
    {
        $driver = empty($GLOBALS['conf']['cache']['driver'])
            ? 'Null'
            : $GLOBALS['conf']['cache']['driver'];
        if (strcasecmp($driver, 'None') === 0) {
            $driver = 'Null';
        }

        if (($driver == 'Null') && !empty($opts['session'])) {
            $driver = 'Session';
        }

        if (!isset($this->_instances[$driver])) {
            $params = Horde::getDriverConfig('cache', $driver);
            if (isset($GLOBALS['conf']['cache']['default_lifetime'])) {
                $params['lifetime'] = $GLOBALS['conf']['cache']['default_lifetime'];
            }

            $logger = $this->_injector->getInstance('Horde_Log_Logger');
            $params['logger'] = $logger;

            $base_params = $params;

            if (strcasecmp($driver, 'Memcache') === 0) {
                $params['memcache'] = $this->_injector->getInstance('Horde_Memcache');
            } elseif (strcasecmp($driver, 'Sql') === 0) {
                $params['db'] = $this->_injector->getInstance('Horde_Db')->getDb('horde', 'cache');
            }

            if (!empty($GLOBALS['conf']['cache']['use_memorycache']) &&
                ((strcasecmp($driver, 'Sql') === 0) ||
                 (strcasecmp($driver, 'File') === 0))) {
                if (strcasecmp($GLOBALS['conf']['cache']['use_memorycache'], 'Memcache') === 0) {
                    $base_params['memcache'] = $this->_injector->getInstance('Horde_Memcache');
                }

                $params = array(
                    'stack' => array(
                        array(
                            'driver' => $GLOBALS['conf']['cache']['use_memorycache'],
                            'params' => $base_params
                        ),
                        array(
                            'driver' => $driver,
                            'params' => $params
                        )
                    )
                );
                $driver = 'Stack';
            }

            $this->_instances[$driver] = Horde_Cache::factory($driver, $params);
        }

        return $this->_instances[$driver];
    }
}
