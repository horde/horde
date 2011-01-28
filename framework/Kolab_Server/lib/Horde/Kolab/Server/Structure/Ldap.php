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
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
     * Returns the set of search operations supported by this object type.
     *
     * @return array An array of supported search operations.
     */
    public function getSearchOperations()
    {
        $searches = array(
            'Horde_Kolab_Server_Search_Operation_Guid',
            'Horde_Kolab_Server_Search_Operation_Attributes',
            'Horde_Kolab_Server_Search_Operation_Children',
            'Horde_Kolab_Server_Search_Operation_Guidforcn',
        );
        return $searches;
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

    public function getExternalAttribute(
        $name,
        Horde_Kolab_Server_Object_Interface $object
    ) {
        $class = ucfirst(strtolower($name));
        $object_attribute_class = 'Horde_Kolab_Server_Object_Attribute_'
            . $class;
        $structure_attribute_class = 'Horde_Kolab_Server_Structure_Attribute_'
            . $class;

        if (class_exists($structure_attribute_class)) {
            $structure_attribute = new $structure_attribute_class($object, $name);
        } else {
            switch ($name) {
            case 'Firstnamelastname':
                $structure_attribute = new Horde_Kolab_Server_Structure_Attribute_Double(
                    $object, array('givenName', 'sn')
                );
                break;
            default:
                $structure_attribute = new Horde_Kolab_Server_Structure_Attribute_Value(
                    $object, $name
                );
                break;
            }
        }

        switch ($name) {
        case 'objectClass':
            $structure_attribute = new Horde_Kolab_Server_Structure_Attribute_Locked(
                $structure_attribute
            );
        default:
            break;
        }


        if (class_exists($object_attribute_class)) {
            $object_attribute = new $object_attribute_class($structure_attribute, $name);
        } else {
            switch ($name) {
            default:
                $object_attribute = new Horde_Kolab_Server_Object_Attribute_Value(
                    $structure_attribute, $name
                );
                break;
            }
        }

/*         case 'Guid': */
/*             return 'dn'; */
/*         case 'Uid': */
/*             return 'uid'; */
/*         case 'Mail': */
/*             return 'mail'; */
/*         case 'Alias': */
/*             return 'alias'; */
/*         case 'Delegate': */
/*             return 'kolabDelegate'; */
/*         case 'Firstnamelastname': */
/*             return array('givenName', 'sn'); */
/*         case 'Openldapaci': */
/*             return 'openLDAPaci'; */
/*         case 'Kolabhomeserver': */
/*             return 'kolabHomeServer'; */
/*         case 'Kolabfreebusyhost': */
/*             //@todo: rename to kolabFreeBusyService(Url) */
/*             return 'kolabFreeBusyServer'; */
/*         case 'Createtimestamp': */
/*         case 'Createtimestampdate': */
/*             return 'createTimeStamp'; */
/*         case 'Modifytimestamp': */
/*         case 'Modifytimestampdate': */
/*             return 'modifyTimeStamp'; */
/*         case 'Id': */
/*             return null; */
/*         default: */
/*             throw new Horde_Kolab_Server_Exception( */
/*                 sprintf('Undefined internal attribute "%s"', $external) */
/*             ); */
/*         } */

        return $object_attribute;
    }

    /**
     * Maps the external attribute name to its internal counterpart(s).
     *
     * @param string $external The external attribute name.
     *
     * @return string The internal attribute name(s).
     */
    private function _mapExternalToInternal($external)
    {
        switch ($external) {
        case 'Guid':
            return 'dn';
        case 'Uid':
            return 'uid';
        case 'Mail':
            return 'mail';
        case 'Alias':
            return 'alias';
        case 'Delegate':
            return 'kolabDelegate';
        case 'Firstnamelastname':
            return array('givenName', 'sn');
        case 'Openldapaci':
            return 'openLDAPaci';
        case 'Kolabhomeserver':
            return 'kolabHomeServer';
        case 'Kolabfreebusyhost':
            //@todo: rename to kolabFreeBusyService(Url)
            return 'kolabFreeBusyServer';
        case 'Createtimestamp':
        case 'Createtimestampdate':
            return 'createTimeStamp';
        case 'Modifytimestamp':
        case 'Modifytimestampdate':
            return 'modifyTimeStamp';
        case 'Id':
            return null;
        default:
            return $external;
        }
    }

    /**
     * Maps the external attribute name to its internal counterpart.
     *
     * @param string $external The external attribute name.
     *
     * @return string The internal attribute name.
     */
    public function mapExternalToInternalAttribute($external)
    {
        $internal = $this->_mapExternalToInternal($external);
        if (is_string($internal)) {
            return $internal;
        } else if (is_array($internal)) {
            throw new Horde_Kolab_Server_Exception('Multiple internal attributes!');
        } else if ($internal === null) {
            throw new Horde_Kolab_Server_Exception('No internal attribute mapping!');
        }
        throw new Horde_Kolab_Server_Exception(
            sprintf(
                'Invalid internal attribute mapping: %s',
                print_r($internal, true)
            )
        );
    }

    /**
     * Maps the external attribute names to their internal counterparts.
     *
     * @param string $external The external attribute names.
     *
     * @return string The internal attribute names.
     */
    public function mapExternalToInternalAttributes(array $external)
    {
        $result = array();
        foreach ($external as $attribute) {
            $internal = $this->_mapExternalToInternal($attribute);
            $result = array_merge($result, (array) $internal);
        }
        $result = array_unique($result);
        return $result;
    }
}
