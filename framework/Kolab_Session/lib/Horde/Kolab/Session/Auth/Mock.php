<?php
/**
 * Mock authentication for the Kolab session information.
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
 * Mock authentication for the Kolab session information.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Session_Auth_Mock
implements Horde_Kolab_Session_Auth_Interface
{
    /**
     * The user this instance will report.
     *
     * @var string
     */
    private $_user;

    /**
     * Constructor
     *
     * @param string $user The user this instance should report.
     */
    public function __construct($user)
    {
        $this->_user = $user;
    }

    /**
     * Get the current user ID.
     *
     * @return string The ID of the current user.
     */
    public function getCurrentUser()
    {
        return $this->_user;
    }
}
