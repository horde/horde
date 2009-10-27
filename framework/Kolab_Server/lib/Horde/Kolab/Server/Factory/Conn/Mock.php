<?php
/**
 * A factory that generates mock connections.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * A factory that generates mock connections.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Factory_Conn_Mock
extends Horde_Kolab_Server_Factory_Conn_Base
{
    /**
     * Return the server connection that should be used.
     *
     * @return Horde_Kolab_Server_Connection The server connection.
     */
    public function getConnection()
    {
        $connection = new Horde_Kolab_Server_Connection_Mock(
            $this->_configuration
        );
        return $connection;
    }
}