<?php
/**
 * A singleton pattern providing Horde_Kolab_Session instances.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * A singleton pattern providing Horde_Kolab_Session instances.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Singleton
{
    /**
     * Horde_Kolab_Session instance.
     *
     * @var Horde_Kolab_Session
     */
    static private $_instance;

    /**
     * Attempts to return a reference to a concrete Horde_Kolab_Session instance.
     *
     * It will only create a new instance if no Horde_Kolab_Session instance
     * currently exists
     *
     * @param string $user        The session will be setup for the user with
     *                            this ID. For Kolab this must either contain
     *                            the user id or the primary user mail address.
     *
     * @param array  $credentials An array of login credentials. For Kolab,
     *                            this must contain a "password" entry.
     *
     * @return Horde_Kolab_Session The concrete Session reference.
     *
     * @throws Horde_Kolab_Session_Exception If the connection failed.
     */
    static public function singleton($user = null, array $credentials = null)
    {
        global $conf;

        if (!isset(self::$_instance)) {
            $config            = $conf['kolab'];
            $config['logger']  = Horde::getLogger();
            $factory = new Horde_Kolab_Session_Factory_Configuration($config);
            self::$_instance = $factory->getSession($user);
        }
        return self::$_instance;
    }
}
