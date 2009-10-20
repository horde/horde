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
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
     * @var Horde_Kolab_Server
     */
    public $server;

    /**
     * The structure handler for this server.
     *
     * @var Horde_Kolab_Server_Structure
     */
    public $structure;

    /**
     * The search handler for this server.
     *
     * @var Horde_Kolab_Server_Search
     */
    public $search;

    /**
     * The object handler for this server.
     *
     * @var Horde_Kolab_Server_Objects
     */
    public $objects;

    /**
     * The schema handler for this server.
     *
     * @var Horde_Kolab_Server_Schema
     */
    public $schema;

    /**
     * Construct a new Horde_Kolab_Server object.
     *
     * @param array $params Parameter array.
     */
    public function __construct(
        Horde_Kolab_Server $server,
        Horde_Kolab_Server_Objects $objects,
        Horde_Kolab_Server_Structure $structure,
        Horde_Kolab_Server_Search $search,
        Horde_Kolab_Server_Schema $schema
    ) {
        $this->server    = $server;
        $this->objects   = $objects;
        $this->structure = $structure;
        $this->search    = $search;
        $this->schema    = $schema;

        $structure->setComposite($this);
        $search->setComposite($this);
        $schema->setComposite($this);
        $objects->setComposite($this);
    }

    /**
     * Connect to the server. Use this method if the user name you can provide
     * does not match a DN. In this case it will be required to map this user
     * name first.
     *
     * @param string $user The user name.
     * @param string $pass The password.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the connection failed.
     */
    protected function _connect($user = null, $pass = null)
    {
        /** Bind anonymously first. */
        $this->connectUid();
        $guid = $this->structure->getGuidForUser($user);
        $this->connectUid($guid, $pass);
        return $this->structure->getUserForUser($user);
    }
}
