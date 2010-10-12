<?php
/**
 * A Horde_Injector:: based Horde_Share:: factory.
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 */

/**
 * A Horde_Injector:: based Horde_Share:: factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 */
class Horde_Core_Factory_Share
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

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
     * Return the Horde_Share:: instance.
     *
     * @param string $app     The application scope to use, if not the current
     *                        app.
     * @param string $driver  The share driver. Either empty (use default
     *                        driver from $conf) or a driver name.
     *
     * @return Horde_Share  The Horde_Share instance.
     * @throws Horde_Exception
     */
    public function create($app = null, $driver = null)
    {
        if (empty($driver)) {
            $driver = $GLOBALS['conf']['share']['driver'];
        }
        if (empty($app)) {
            $app = $this->_injector->getInstance('Horde_Registry')->getApp();
        }

        $sig = $app . '_' . $driver;

        if (isset($this->_instances[$sig])) {
            return $this->_instances[$sig];
        }

        if (!empty($GLOBALS['conf']['share']['cache'])) {
            $cache_sig = 'horde_share/' . $app . '/' . $driver . '1';
            $ob = $GLOBALS['session']->retrieve($cache_sig);
        }

        if (empty($ob)) {
            $class = 'Horde_Share_' . ucfirst(basename($driver));
            if (!class_exists($class)) {
                $dict = new Horde_Translation_Gettext('Horde_Core', dirname(__FILE__) . '/../../../../locale');
                throw new Horde_Exception(sprintf($dict->t("\"%s\" share driver not found."), $driver));
            }

            $ob = new $class($app, $this->_injector->getInstance('Horde_Perms'));
        }

        if (!empty($GLOBALS['conf']['share']['cache'])) {
            register_shutdown_function(array($this, 'shutdown'), $cache_sig, $ob);
        }

        $this->_instances[$sig] = $ob;

        return $ob;
    }

    /**
     * Shutdown function.
     *
     * @param string $sig         Cache signature.
     * @param Horde_Share $share  Horde_Share object to cache.
     */
    public function shutdown($sig, $share)
    {
        $GLOBALS['session']->store($share, false, $sig);
    }

}
