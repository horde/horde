<?php
/**
 * A simple structural handler for a tree of objects.
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
 * The interface definition for the handlers dealing with the Kolab Server
 * object tree structure.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
interface Horde_Kolab_Server_Structure_Interface
{
    /**
     * Finds object data matching a given set of criteria.
     *
     * @param Horde_Kolab_Server_Query_Element $criteria The criteria for the search.
     * @param array                            $params   Additional search parameters.
     *
     * @return Horde_Kolab_Server_Result The result object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function find(
        Horde_Kolab_Server_Query_Element_Interface $criteria,
        array $params = array()
    );

    /**
     * Finds all object data below a parent matching a given set of criteria.
     *
     * @param Horde_Kolab_Server_Query_Element $criteria The criteria for the search.
     * @param string                           $parent   The parent to search below.
     * @param array                            $params   Additional search parameters.
     *
     * @return Horde_Kolab_Server_Result The result object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function findBelow(
        Horde_Kolab_Server_Query_Element_Interface $criteria,
        $parent,
        array $params = array()
    );

    /**
     * Set the composite server reference for this object.
     *
     * @param Horde_Kolab_Server_Composite $composite A link to the composite
     *                                                server handler.
     *
     * @return NULL
     */
    public function setComposite(
        Horde_Kolab_Server_Composite $composite
    );

    /**
     * Returns the set of objects supported by this structure.
     *
     * @return array An array of supported objects.
     */
    public function getSupportedObjects();

    /**
     * Returns the set of search operations supported by this object type.
     *
     * @return array An array of supported search operations.
     */
    public function getSearchOperations();

    /**
     * Maps the external attribute name to its internal counterpart.
     *
     * @param string $external The external attribute name.
     *
     * @return string The internal attribute name.
     */
    public function mapExternalToInternalAttribute($external);

    /**
     * Return the external attributes supported by the given object class.
     *
     * @param Horde_Kolab_Server_Object $object Determine the external
     *                                          attributes for this class.
     *
     * @return array The supported attributes.
     *
     * @throws Horde_Kolab_Server_Exception If the schema analysis fails.
     */
    public function getExternalAttributes($object);

    /**
     * Return the internal attributes supported by the given object class.
     *
     * @param Horde_Kolab_Server_Object $object Determine the internal
     *                                          attributes for this class.
     *
     * @return array The supported attributes.
     *
     * @throws Horde_Kolab_Server_Exception If the schema analysis fails.
     */
    public function getInternalAttributes($object);

    public function getExternalAttribute(
        $name,
        Horde_Kolab_Server_Object_Interface $object
    );

    /**
     * Determine the type of an object by its tree position and other
     * parameters.
     *
     * @param string $guid The GUID of the object to examine.
     *
     * @return string The class name of the corresponding object type.
     *
     * @throws Horde_Kolab_Server_Exception If the object type is unknown.
     */
    public function determineType($guid);

    /**
     * Generates a UID for the given information.
     *
     * @param string $type The class name of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string The GUID.
     *
     * @throws Horde_Kolab_Server_Exception If the given type is unknown.
     */
    public function generateServerGuid($type, $id, array $info);
}
