<?php
/**
 * Defines storage containers for the Kolab session information.
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
 * Defines storage containers for the Kolab session information.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Session_Storage_Session
implements Horde_Kolab_Session_Storage
{
    /**
     * Load the session information.
     *
     * @return array The session data or an empty array if no information was
     *               found.
     */
    public function load()
    {
        if (isset($_SESSION['kolab_session'])) {
            return $_SESSION['kolab_session'];
        } else {
            return array();
        }
    }

    /**
     * Save the session information.
     *
     * @param array $session_data The session data that should be stored.
     *
     * @return NULL
     */
    public function save(array $session_data)
    {
        $_SESSION['kolab_session'] = $session_data;
    }
}
