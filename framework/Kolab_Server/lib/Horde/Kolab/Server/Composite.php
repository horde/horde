<?php
/**
 * A simple composition of server functionality.
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
 * A simple composition of server functionality.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Composite
{
    /**
     * The server.
     *
     * @var Horde_Kolab_Server_Interface
     */
    private $_server;

    /**
     * The structure handler for this server.
     *
     * @var Horde_Kolab_Server_Structure_Interface
     */
    private $_structure;

    /**
     * The search handler for this server.
     *
     * @var Horde_Kolab_Server_Search_Interface
     */
    private $_search;

    /**
     * The object handler for this server.
     *
     * @var Horde_Kolab_Server_Objects_Interface
     */
    private $_objects;

    /**
     * The schema handler for this server.
     *
     * @var Horde_Kolab_Server_Schema_Interface
     */
    private $_schema;

    /**
     * Construct a new Horde_Kolab_Server object.
     *
     * @param array $params Parameter array.
     */
    public function __construct(
        Horde_Kolab_Server_Interface $server,
        Horde_Kolab_Server_Objects_Interface $objects,
        Horde_Kolab_Server_Structure_Interface $structure,
        Horde_Kolab_Server_Search_Interface $search,
        Horde_Kolab_Server_Schema_Interface $schema
    ) {
        $this->_server    = $server;
        $this->_objects   = $objects;
        $this->_structure = $structure;
        $this->_search    = $search;
        $this->_schema    = $schema;

        $structure->setComposite($this);
        $search->setComposite($this);
        $schema->setComposite($this);
        $objects->setComposite($this);
    }

    /**
     * Retrieve an object attribute.
     *
     * @param string $key The name of the attribute.
     *
     * @return mixed The atribute value.
     *
     * @throws Horde_Kolab_Server_Exception If the attribute does not exist.
     */
    public function __get($key)
    {
        $public = array('server', 'objects', 'structure', 'search', 'schema');
        if (in_array($key, $public)) {
            $priv_key = '_' . $key;
            return $this->$priv_key;
        }
        throw new Horde_Kolab_Server_Exception(
            sprintf('Attribute %s not supported!', $key)
        );
    }

    /**
     * Connect to the server. Use this method if the user name you can provide
     * does not match a GUID. In this case it will be required to map this user
     * name first.
     *
     * @param string $user The user name.
     * @param string $pass The password.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the connection failed.
     */
    public function connect($user = null, $pass = null)
    {
        /** Bind anonymously first. */
        $this->server->connectGuid();
        $guid = $this->search->searchGuidForUidOrMail($user);
        $this->server->connectGuid($guid, $pass);
    }
}
