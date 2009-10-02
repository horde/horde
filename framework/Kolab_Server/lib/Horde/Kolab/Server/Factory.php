<?php
/**
 * A library for accessing the Kolab user database.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * A factory for Kolab server objects.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Factory
{
    /**
     * Attempts to return a concrete Horde_Kolab_Server instance.
     *
     * @param Horde_Injector $injector The object providing our dependencies.
     *
     * @return Horde_Kolab_Server The newly created concrete Horde_Kolab_Server
     *                            instance.
     *
     * @throws Horde_Kolab_Server_Exception If the requested Horde_Kolab_Server
     *                                      subclass could not be found.
     */
    static public function &getServer(Horde_Injector $injector)
    {
        $driver = 'Horde_Kolab_Server_Ldap';
        $params = array();

        try {
            $config = $injector->getInstance('Horde_Kolab_Server_Config');

            if (isset($config->driver)) {
                $driver = $config->driver;
            }
            if (isset($config->params)) {
                $params = $config->params;
            }
        } catch (ReflectionException $e) {
        }

        $class = 'Horde_Kolab_Server_' . ucfirst(basename($driver));
        if (!class_exists($class)) {
            throw new Horde_Kolab_Server_Exception('Server type definition "' . $class . '" missing.');
        }

        $server = new $class($injector->getInstance('Horde_Kolab_Server_Structure'),
                             $params);

        try {
            $server->setCache($injector->getInstance('Horde_Kolab_Server_Cache'));
        } catch (ReflectionException $e) {
        }

        try {
            $server->setLogger($injector->getInstance('Horde_Kolab_Server_Logger'));
        } catch (ReflectionException $e) {
        }

        return $server;
    }

    /**
     * Attempts to return a concrete Horde_Kolab_Server_Structure instance.
     *
     * @param Horde_Injector $injector The object providing our dependencies.
     *
     * @return Horde_Kolab_Server_Structure The newly created concrete
     *                                      Horde_Kolab_Server_Structure
     *                                      instance.
     *
     * @throws Horde_Kolab_Server_Exception If the requested
     *                                      Horde_Kolab_Server_Structure
     *                                      subclass could not be found.
     */
    static public function &getStructure(Horde_Injector $injector)
    {
        $driver = 'Horde_Kolab_Server_Structure_Kolab';
        $params = array();

        try {
            $config = $injector->getInstance('Horde_Kolab_Server_Structure_Config');

            if (isset($config->driver)) {
                $driver = $config->driver;
            }
            if (isset($config->params)) {
                $params = $config->params;
            }
        } catch (ReflectionException $e) {
        }

        //@todo: either we use driver names or real class names.
        //$class = 'Horde_Kolab_Server_Structure_' . ucfirst(basename($driver));
        if (!class_exists($driver)) {
            throw new Horde_Kolab_Server_Exception('Structure type definition "' . $driver . '" missing.');
        }
        $structure = new $driver($params);
        return $structure;
    }
}