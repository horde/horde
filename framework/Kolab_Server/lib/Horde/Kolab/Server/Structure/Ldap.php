<?php
/**
 * A structural handler for the tree of objects stored in LDAP.
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
 * This class provides methods to deal with the LDAP tree structure.
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
class Horde_Kolab_Server_Structure_Ldap extends Horde_Kolab_Server_Structure_Base
{
    /**
     * Returns the set of objects supported by this structure.
     *
     * @return array An array of supported objects.
     */
    public function getSupportedObjects()
    {
        return array(
            'Horde_Kolab_Server_Object',
        );
    }

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
    public function determineType($guid)
    {
        $ocs = $this->getObjectClasses($guid);
        return $this->_determineType($guid, $ocs);
    }

    /**
     * Determine the type of an object by its tree position and other
     * parameters.
     *
     * @param string $guid The GUID of the object to examine.
     * @param array  $ocs  The object classes of the object to examine.
     *
     * @return string The class name of the corresponding object type.
     *
     * @throws Horde_Kolab_Server_Exception If the object type is unknown.
     */
    protected function _determineType($guid, array $ocs)
    {
        $ocs = array_reverse($ocs);
        foreach ($ocs as $oc) {
            try {
                $class_name = 'Horde_Kolab_Server_Object_' . ucfirst(strtolower($oc));
                Horde_Kolab_Server_Object_Factory::loadClass($class_name);
                return $class_name;
            } catch (Horde_Kolab_Server_Exception $e)  {
            }
        }
        throw new Horde_Kolab_Server_Exception(
            sprintf("Unknown object type for GUID %s.", $guid),
            Horde_Kolab_Server_Exception::SYSTEM
        );
    }

    /**
     * Generates a GUID for the given information.
     *
     * @param string $type The class name of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string The GUID.
     */
    public function generateServerGuid($type, $id, array $info)
    {
        return sprintf('%s,%s', $id, $this->getComposite()->server->getBaseGuid());
    }

    /**
     * Get the LDAP object classes for the given GUID.
     *
     * This is meant to be a shortcut for the structure handler. It should be
     * used when determining the object type.
     *
     * @param string $guid GUID of the object.
     *
     * @return array An array of object classes.
     *
     * @throws Horde_Kolab_Server_Exception If the object has no
     *                                      object classes.
     */
    protected function getObjectClasses($guid)
    {
        $object = $this->getComposite()->server->read(
            $guid, array('objectClass')
        );
        if (!isset($object['objectClass'])) {
            throw new Horde_Kolab_Server_Exception(
                sprintf(
                    "The object %s has no %s attribute!",
                    $guid, 'objectClass'
                ),
                Horde_Kolab_Server_Exception::SYSTEM
            );
        }
        $result = array_map(
            'strtolower',
            $object['objectClass']
        );
        return $result;
    }

    /**
     * Maps the external attribute name to its internal counterpart.
     *
     * @param string $external The external attribute name.
     *
     * @return string The internal attribute name.
     */
    public function getInternalAttribute($external)
    {
        switch ($external) {
        case 'Objectclass':
            return 'objectClass';
        case 'Guid':
            return 'dn';
        case 'Uid':
            return 'uid';
        case 'Mail':
            return 'mail';
        default:
            throw new Horde_Kolab_Server_Exception(
                sprintf('Undefined internal attribute "%s"', $external)
            );
        }
    }
}
