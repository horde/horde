<?php
/**
 * A library for accessing the Kolab user database.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * This class provides the standard error class for Kolab Server exceptions.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Exception extends Horde_Exception_Wrapped
{
    /**
     * Constants to define the error type.
     */

    /** Unknown error type */
    const SYSTEM                     = 1;

    /** The LDAP extension is missing */
    const MISSING_LDAP_EXTENSION     = 2;

    /** Binding to the LDAP server failed */
    const BIND_FAILED                = 3;

    /** The resultset was empty */
    const EMPTY_RESULT               = 4;

    const INVALID_INFORMATION        = 5;

    /** The query was invalid */
    const INVALID_QUERY              = 6;

    /** The search yielded too many results */
    const SEARCH_CONSTRAINT_TOO_MANY = 7;
}
