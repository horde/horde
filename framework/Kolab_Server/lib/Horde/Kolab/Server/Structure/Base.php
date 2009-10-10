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
 * An abstract class definiing methods to deal with an object tree structure.
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
abstract class Horde_Kolab_Server_Structure_Base implements Horde_Kolab_Server_Structure
{
    /**
     * A link to the server handler.
     *
     * @var Horde_Kolab_Server
     */
    protected $server;

    /**
     * Set the server reference for this object.
     *
     * @param Horde_Kolab_Server &$server A link to the server handler.
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * Returns the set of objects supported by this structure.
     *
     * @return array An array of supported objects.
     */
    public function getSupportedObjects()
    {
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
    }

    /**
     * Get the LDAP object classes for the given DN.
     *
     * This is meant to be a shortcut for the structure handler. It should be
     * used when determining the object type.
     *
     * @param string $uid DN of the object.
     *
     * @return array An array of object classes.
     *
     * @throws Horde_Kolab_Server_Exception If the object has no
     *                                      object classes.
     */
    public function getObjectClasses($uid)
    {
        $object = $this->read($uid, array(Horde_Kolab_Server_Object::ATTRIBUTE_OC));
        if (!isset($object[Horde_Kolab_Server_Object::ATTRIBUTE_OC])) {
            throw new Horde_Kolab_Server_Exception(
                sprintf(
                    "The object %s has no %s attribute!",
                    $uid, Horde_Kolab_Server_Object::ATTRIBUTE_OC
                ),
                Horde_Kolab_Server_Exception::SYSTEM
            );
        }
        $result = array_map(
            'strtolower',
            $object[Horde_Kolab_Server_Object::ATTRIBUTE_OC]
        );
        return $result;
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
