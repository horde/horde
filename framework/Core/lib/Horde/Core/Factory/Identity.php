<?php
/**
 * A Horde_Injector:: based Horde_Identity:: factory.
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
 * A Horde_Injector:: based Horde_Identity:: factory.
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
class Horde_Core_Factory_Identity
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
     * Return the Horde_Identity:: instance.
     *
     * @param string $user    The user to use, if not the current user.
     * @param string $driver  The identity driver. Either empty (use default
     *                        driver) or an application name.
     *
     * @return Horde_Identity  The singleton identity instance.
     * @throws Horde_Exception
     */
    public function getIdentity($user = null, $driver = null)
    {
        global $injector, $prefs, $registry;

        $class = empty($driver)
            ? 'Horde_Core_Prefs_Identity'
            : Horde_String::ucfirst($driver) . '_Prefs_Identity';
        $key = $class . '|' . $user;

        if (!isset($this->_instances[$key])) {
            if (!class_exists($class)) {
                throw new Horde_Exception('Class definition of ' . $class . ' not found.');
            }

            $params = array(
                'user' => is_null($user) ? $registry->getAuth() : $user,
            );

            if (isset($prefs) && ($params['user'] == $registry->getAuth())) {
                $params['prefs'] = $prefs;
            } else {
                $params['prefs'] = $injector->getInstance('Horde_Prefs')->getPrefs($registry->getApp(), array(
                    'cache' => false,
                    'user' => $user
                ));
                $params['prefs']->retrieve();
            }

            $this->_instances[$key] = new $class($params);
            $this->_instances[$key]->init();
        }

        return $this->_instances[$key];
    }

}
