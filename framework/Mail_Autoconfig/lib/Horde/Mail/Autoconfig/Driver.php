<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mail_Autoconfig
 */

/**
 * Abstract class for implementing a mail configuration lookup driver.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mail_Autoconfig
 */
abstract class Horde_Mail_Autoconfig_Driver
{
    /**
     * The priority of this driver. Lower values (0 minimum) indicate higher
     * priority.
     *
     * @var integer
     */
    public $priority;

    /**
     * Determine the configuration for a message submission agent (MSA).
     *
     * @param array $domains          List of domains to search.
     * @param array $opts             Additional options:
     *   - email: (Horde_Mail_Rfc822_Address) The original e-mail provided.
     *
     * @return mixed  False if no servers found, or a list of server objects
     *                in order of decreasing priority.
     * @throws Horde_Mail_Autoconfig_Exception
     */
    abstract public function msaSearch($domains, array $opts = array());

    /**
     * Determine the configuration for a message storage access server.
     *
     * @param array $domains          List of domains to search.
     * @param array $opts             Additional options:
     *   - email: (Horde_Mail_Rfc822_Address) The original e-mail provided.
     *   - no_imap: (boolean) If true, ignore IMAP servers.
     *   - no_pop3: (boolean) If true, ignore POP3 servers.
     *
     * @return mixed  False if no servers found, or a list of server objects
     *                in order of decreasing priority.
     * @throws Horde_Mail_Autoconfig_Exception
     */
    abstract public function mailSearch($domains, array $opts = array());

}
