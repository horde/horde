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
     * @param string $uid The UID of the object to examine.
     *
     * @return string The class name of the corresponding object type.
     *
     * @throws Horde_Kolab_Server_Exception If the object type is unknown.
     */
    public function determineType($uid)
    {
        if (empty($this->server)) {
            throw new Horde_Kolab_Server_Exception('The server reference is missing!');
        }
        $ocs = $this->server->getObjectClasses($uid);
        $ocs = array_reverse($ocs);
        foreach ($ocs as $oc) {
            try {
                $class_name = 'Horde_Kolab_Server_Object_' . ucfirst(strtolower($oc));
                Horde_Kolab_Server_Object::loadClass($class_name);
                return $class_name;
            } catch (Horde_Kolab_Server_Exception $e)  {
            }
        }
        if ($oc == 'top') {
            return 'Horde_Kolab_Server_Object';
        }
        throw new Horde_Kolab_Server_Exception(sprintf(_("Unkown object type for UID %s."),
                                                       $uid));
    }

    /**
     * Generates a UID for the given information.
     *
     * @param string $type The class name of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string The UID.
     *
     * @throws Horde_Kolab_Server_Exception If the given type is unknown.
     */
    public function generateServerUid($type, $id, $info)
    {
        if (empty($this->server)) {
            throw new Horde_Kolab_Server_Exception('The server reference is missing!');
        }
        return sprintf('%s,%s', $id, $this->server->getBaseUid());
    }

}
