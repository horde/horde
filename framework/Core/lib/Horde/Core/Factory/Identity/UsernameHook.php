<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

/**
 * A Horde_Injector based Horde_Identity factory that converts the user name
 * through the user name hook.
 *
 * @category Horde
 * @package  Core
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Core_Factory_Identity_UsernameHook extends Horde_Core_Factory_Identity
{
    /**
     * Returns the Horde_Identity instance.
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
        return parent::create(
            $GLOBALS['registry']->convertUsername($user, true),
            $driver
        );
    }
}
