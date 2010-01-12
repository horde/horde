<?php
/**
 * The interface of Kolab server factories.
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
 * The interface of Kolab server factories.
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
interface Horde_Kolab_Server_Factory_Interface
{
    /**
     * Returns a concrete Horde_Kolab_Server_Composite instance.
     *
     * @return Horde_Kolab_Server_Composite The newly created concrete
     *                                      Horde_Kolab_Server_Composite
     *                                      instance.
     */
    public function getComposite();

    /**
     * Returns the conn factory.
     *
     * @return Horde_Kolab_Server_Factory_Conn The connection factory.
     */
    public function getConnectionFactory();

    /**
     * Returns the server configuration parameters.
     *
     * @return array The configuration parameters.
     */
    public function getConfiguration();

    /**
     * Return the server connection that should be used.
     *
     * @return Horde_Kolab_Server The Horde_Kolab_Server connection.
     */
    public function getServer();

    /**
     * Return the server that should be used.
     *
     * @return Horde_Kolab_Server_Connection The connection.
     */
    public function getConnection();

    /**
     * Return the object handler that should be used.
     *
     * @return Horde_Kolab_Server_Objects The handler for objects on the server.
     */
    public function getObjects();

    /**
     * Return the structural representation that should be used.
     *
     * @return Horde_Kolab_Server_Structure The representation of the db
     *                                      structure.
     */
    public function getStructure();

    /**
     * Return the search handler that should be used.
     *
     * @return Horde_Kolab_Server_Search The search handler.
     */
    public function getSearch();

    /**
     * Return the db schema representation that should be used.
     *
     * @return Horde_Kolab_Server_Schema The db schema representation.
     */
    public function getSchema();
}