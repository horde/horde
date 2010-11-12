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
class Horde_Core_Factory_ShareBase
{
    public function create($app = null, $driver = null)
    {
        if (empty($driver)) {
            $driver = $GLOBALS['conf']['share']['driver'];
        }
        if (empty($app)) {
            $app = $GLOBALS['injector']->getInstance('Horde_Registry')->getApp();
        }

        $sig = $app . '_' . $driver;
        if (isset($this->_instances[$sig])) {
            return $this->_instances[$sig];
        }

        $class = 'Horde_Share_' . ucfirst(basename($driver));
        if (!class_exists($class)) {
            throw new Horde_Exception(sprintf(Horde_Core_Translation::t("\"%s\" share driver not found."), $driver));
        }

        $ob = new $class($app, $GLOBALS['registry']->getAuth(), $GLOBALS['injector']->getInstance('Horde_Perms'), $GLOBALS['injector']->getInstance('Horde_Group'));
        $cb = new Horde_Core_Share_FactoryCallback($app, $driver);
        $ob->setShareCallback(array($cb, 'create'));
        if (!empty($GLOBALS['conf']['share']['cache'])) {
            $cache_sig = 'horde_share/' . $app . '/' . $driver;
            $listCache = $GLOBALS['session']->retrieve($cache_sig);
            $ob->setListCache($listCache);
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
        $GLOBALS['session']->store($share->getListCache(), false, $sig);
    }

}