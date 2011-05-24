<?php
/**
 * The Horde_Kolab_Session_Factory_Imap class allows to dependency inject the
 * IMAP client.
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
 * The Horde_Kolab_Session_Factory_Imap class allows to dependency inject the
 * IMAP client.
 *
 * @todo Rename from Horde_Kolab_Session_Base ->
 * Horde_Kolab_Session_Ldap at some point.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Session_Factory_Imap
{
    /**
     * Create the IMAP client.
     *
     * @param array $params The connection parameters for the IMAP client.
     *
     * @return Horde_Imap_Client_Base The IMAP client.
     */
    public function create($params)
    {
        return Horde_Imap_Client::factory('Socket', $params);
    }
}
