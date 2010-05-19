<?php
/**
 * A Horde_Injector:: based Horde_Share:: factory.
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 */

/**
 * A Horde_Injector:: based Horde_Identity:: factory.
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
    public function getScope($app = null, $driver = null)
    {
        if (empty($driver)) {
            $driver = $GLOBALS['conf']['share']['driver'];
        }
        if (empty($app)) {
            $app = $this->_injector->getInstance('Horde_Registry')->getApp();
        }

        $class = 'Horde_Share_' . ucfirst(basename($driver));
        $signature = $app . '_' . $driver;
        if (!isset($this->_instances[$signature]) &&
            !empty($GLOBALS['conf']['share']['cache'])) {
            
            $session = new Horde_SessionObjects();
            $shares[$signature] = $session->query('horde_share_' . $app . '_' . $driver . '1');
        }

        if (empty($shares[$signature])) {
            if (!class_exists($class)) {
                throw new Horde_Exception((sprintf(_("\"%s\" share driver not found."), $driver)));
            }
            
            $shares[$signature] = new $class($app);
        }

        if (!isset($shares[$signature]) &&
            !empty($GLOBALS['conf']['share']['cache'])) {
            $session = new Horde_SessionObjects();
            $shares[$signature] = $session->query('horde_share_' . $app . '_' . $driver . '1');
        }

        if (!empty($GLOBALS['conf']['share']['cache'])) {
            register_shutdown_function(array($shares[$signature], 'shutdown'));
        }

        return $shares[$signature];
    }

}
