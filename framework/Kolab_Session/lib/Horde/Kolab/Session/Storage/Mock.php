<?php
/**
 * A mock container for the Kolab session information.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * A mock container for the Kolab session information.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Storage_Mock
implements Horde_Kolab_Session_Storage
{
    /**
     * The session information.
     */
    public $session;

    /**
     * Load the session information.
     *
     * @return array The session data or an empty array if no information was
     *               found.
     */
    public function load()
    {
        return false;
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
        $this->session = $session_data;
    }
}
