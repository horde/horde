<?php
/**
 * A Horde_Injector:: based Horde_Identity:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Identity:: factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Identity extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

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
    public function create($user = null, $driver = null)
    {
        global $prefs, $registry;

        $class = 'Horde_Core_Prefs_Identity';
        switch ($driver) {
        case 'horde':
            // Bug #9936: There is a conflict between the horde/Prefs
            // Identity base driver and the application-specific Identity
            // driver for Horde.
            $temp_class = 'Horde_Prefs_HordeIdentity';
            if (class_exists($temp_class)) {
                $class = $temp_class;
            }
            break;

        default:
            if (!is_null($driver)) {
                $class = Horde_String::ucfirst($driver) . '_Prefs_Identity';
                if (!class_exists($class)) {
                    throw new Horde_Exception($driver . ' identity driver does not exist.');
                }
            }
            break;
        }
        $key = $class . '|' . $user;

        if (!isset($this->_instances[$key])) {
            $params = array(
                'user' => is_null($user) ? $registry->getAuth() : $user,
            );

            if (isset($prefs) && ($params['user'] == $registry->getAuth())) {
                $params['prefs'] = $prefs;
            } else {
                $params['prefs'] = $this->_injector->getInstance('Horde_Core_Factory_Prefs')->create($registry->getApp(), array(
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
