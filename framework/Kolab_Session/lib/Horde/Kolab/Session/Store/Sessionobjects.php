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

/** We need the Auth library */
require_once 'Horde/Auth.php';

/**
 * Defines storage containers for the Kolab session information.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Session_Store_Sessionobjects
implements Horde_Kolab_Session_Store
{
    /**
     * Load the session information.
     *
     * @return Horde_Kolab_Session|boolean The session information or false if
     * it could not be loaded.
     */
    public function load()
    {
        return $this->getSessionObjects()->query('kolab_session');
    }

    /**
     * Save the session information.
     *
     * @param Horde_Kolab_Session $session The session information.
     *
     * @return NULL
     */
    public function save(Horde_Kolab_Session $session)
    {
        $this->getSessionObjects()->overwrite('kolab_session', $session, false);
    }

    /**
     * Fetch the handler for session objects.
     *
     * @return Horde_SessionObjects The session objects.
     */
    private function _getSessionObjects()
    {
        return Horde_SessionObjects::singleton();
    }
}
