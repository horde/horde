<?php
/**
 * A Kolab server factory that receives all required details via the
 * factory constructor.
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
 * A Kolab server factory that receives all required details via the
 * factory constructor.
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
class Horde_Kolab_Server_Factory_Constructor
extends Horde_Kolab_Server_Factory_Base
{
    /**
     * The implementation representing the db structur.
     *
     * @param Horde_Kolab_Server_Structure
     */
    private $_structure;

    /**
     * The search handler.
     *
     * @param Horde_Kolab_Server_Search
     */
    private $_search;

    /**
     * Handles the db schema.
     *
     * @param Horde_Kolab_Server_Schema
     */
    private $_schema;

    /**
     * The object handler.
     *
     * @param Horde_Kolab_Server_Objects
     */
    private $_objects;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Server_Factory_Conn $factory   The connection
     *                                                   factory.
     * @param Horde_Kolab_Server_Objects      $objects   The object handler.
     * @param Horde_Kolab_Server_Structure    $structure The implementation
     *                                                   representing the db
     *                                                   structure.
     * @param Horde_Kolab_Server_Search       $search    The search handler.
     * @param Horde_Kolab_Server_Schema       $schema    Handles the db schema.
     * @param array                           $config    Configuration
     *                                                   parameters for the
     *                                                   server.
     */
    public function __construct(
        Horde_Kolab_Server_Factory_Connection_Interface $factory,
        Horde_Kolab_Server_Objects_Interface $objects,
        Horde_Kolab_Server_Structure_Interface $structure,
        Horde_Kolab_Server_Search_Interface $search,
        Horde_Kolab_Server_Schema_Interface $schema,
        array $config
    ) {
        parent::__construct($factory, $config);

        $this->_objects       = $objects;
        $this->_structure     = $structure;
        $this->_search        = $search;
        $this->_schema        = $schema;
    }

    /**
     * Return the object handler that should be used.
     *
     * @return Horde_Kolab_Server_Objects The handler for objects on the server.
     */
    public function getObjects()
    {
        return $this->_objects;
    }

    /**
     * Return the structural representation that should be used.
     *
     * @return Horde_Kolab_Server_Structure The representation of the db
     *                                      structure.
     */
    public function getStructure()
    {
        return $this->_structure;
    }

    /**
     * Return the search handler that should be used.
     *
     * @return Horde_Kolab_Server_Search The search handler.
     */
    public function getSearch()
    {
        return $this->_search;
    }

    /**
     * Return the db schema representation that should be used.
     *
     * @return Horde_Kolab_Server_Schema The db schema representation.
     */
    public function getSchema()
    {
        return $this->_schema;
    }

}