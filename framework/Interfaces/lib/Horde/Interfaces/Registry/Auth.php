<?php
/**
 * Defines a provider of authentication information.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Interfaces
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Interfaces
 */

/**
 * Defines a provider of authentication information.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Interfaces
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Interfaces
 */
interface Horde_Interfaces_Registry_Auth
{
    /**
     * Returns the currently logged in user, if there is one.
     *
     * @param string $format  The return format, defaults to the unique Horde
     *                        ID. Alternative formats:
     *                        - bare: Horde ID without any domain information
     *                          (e.g., foo@example.com would be returned as
     *                          'foo').
     *                        - domain: Domain of the Horde ID (e.g.,
     *                          foo@example.com would be returned as
     *                          'example.com').
     *                        - original: The username used to originally login
     *                          to Horde.
     *
     * @return mixed  The user ID or false if no user is logged in.
     */
    public function getAuth($format = null);
}
