<?php
/**
 * A library for accessing the Kolab user database.
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
 * This class provides methods to deal with Kolab objects stored in
 * the Kolab object db.
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
class Horde_Kolab_Server_Objects_Base
implements Horde_Kolab_Server_Objects_Interface
{
    /**
     * A link to the composite server handler.
     *
     * @var Horde_Kolab_Server_Composite
     */
    private $_composite;

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
    ) {
        $this->_composite = $composite;
    }

    /**
     * Fetch a Kolab object.
     *
     * This method will not retrieve any data from the server
     * immediately. Instead it will simply generate a new instance for the
     * desired object.
     *
     * The server data will only be accessed once you start reading the object
     * data.
     *
     * This method can also be used in order to fetch non-existing objects that
     * will be saved later. This is however not recommended and you should
     * rather use the add($info) method for that.
     *
     * If you do not provide the object type the server will try to determine it
     * automatically based on the uid. As this requires reading data from the
     * server it is recommended to specify the object type whenever it is known.
     *
     * If you do not specify a uid the object corresponding to the user bound to
     * the server will be returned.
     *
     * @param string $guid The GUID of the object to fetch.
     * @param string $type The type of the object to fetch.
     *
     * @return Kolab_Object The corresponding Kolab object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function fetch($guid = null, $type = null)
    {
        if (!isset($guid)) {
            $guid = $this->_composite->server->getGuid();
        }
        if (empty($type)) {
            $type = $this->_composite->structure->determineType($guid);
        }

        $object = Horde_Kolab_Server_Object_Factory::factory(
            $type, $guid, $this->_composite
        );
        return $object;
    }

    /**
     * Add a Kolab object.
     *
     * @param array $info The object to store.
     *
     * @return Kolab_Object The newly created Kolab object.
     *
     * @throws Horde_Kolab_Server_Exception If the type of the object to add has
     *                                      been left undefined or the object
     *                                      already exists.
     */
    public function add(array $info)
    {
        if (!isset($info['type'])) {
            throw new Horde_Kolab_Server_Exception(
                'The type of a new object must be specified!');
        }

        $type = $info['type'];
        unset($info['type']);
        $object = &Horde_Kolab_Server_Object::factory($type, null, $this, $info);
        if ($object->exists()) {
            throw new Horde_Kolab_Server_Exception(
                sprintf("The object with the uid \"%s\" does already exist!",
                        $object->get(Horde_Kolab_Server_Object::ATTRIBUTE_UID)));
        }
        $object->save();
        return $object;
    }

    /**
     * Generate a hash representation for a list of objects.
     *
     * The approach taken here is somewhat slow as the server data gets fetched
     * into objects first which are then converted to hashes again. Since a
     * server search will usually deliver the result as a hash the intermediate
     * object conversion is inefficient.
     *
     * But as the object classes are able to treat the attributes returned from
     * the server with custom parsing, this is currently the preferred
     * method. Especially for large result sets it would be better if this
     * method would call a static object class function that operate on the
     * result array returned from the server without using objects.
     *
     * @param string $type   The type of the objects to be listed
     * @param array  $params Additional parameters.
     *
     * @return array An array of Kolab objects.
     *
     * @throws Horde_Kolab_Server_Exception
     *
     * @todo The LDAP driver needs a more efficient version of this call as it
     *       is not required to generate objects before returning data as a
     *       hash. It can be derived directly from the LDAP result.
     */
    public function listHash($type, $params = null)
    {
        $list = $this->listObjects($type, $params);

        if (isset($params['attributes'])) {
            $attributes = $params['attributes'];
        } else {
            $attributes = null;
        }

        $hash = array();
        foreach ($list as $uid => $entry) {
            $hash[$uid] = $entry->toHash($attributes);
        }

        return $hash;
    }


    /**
     * List all objects of a specific type.
     *
     * @param string $type   The type of the objects to be listed
     * @param array  $params Additional parameters.
     *
     * @return array An array of Kolab objects.
     *
     * @throws Horde_Kolab_Server_Exception
     *
     * @todo Sorting
     * @todo Is this LDAP specific?
     */
    public function listObjects($type, $params = null)
    {
        if (empty($params['base_dn'])) {
            $base = $this->_base_dn;
        } else {
            $base = $params['base_dn'];
        }

        $result   = Horde_Kolab_Server_Object::loadClass($type);
        $vars     = get_class_vars($type);
        $criteria = call_user_func(array($type, 'getFilter'));
        $filter   = $this->searchQuery($criteria);
        $sort     = $vars['sort_by'];

        if (isset($params['sort'])) {
            $sort = $params['sort'];
        }

        $options = array('scope' => 'sub');
        if (isset($params['attributes'])) {
            $options['attributes'] = $params['attributes'];
        } else {
            $options['attributes'] = $this->getAttributes($type);
        }

        $data = $this->search($filter, $options, $base);
        if (empty($data)) {
            return array();
        }

        if ($sort) {
            /* @todo: sorting */
            /* $data = $result->as_sorted_struct(); */
            /*$this->sort($result, $sort); */
        }

        if (isset($params['from'])) {
            $from = $params['from'];
        } else {
            $from = -1;
        }

        if (isset($params['to'])) {
            $sort = $params['to'];
        } else {
            $to = -1;
        }

        if (!empty($vars['required_group'])) {
            $required_group = new Horde_Kolab_Server_Object_Kolabgroupofnames($this,
                                                                              null,
                                                                              $vars['required_group']);
        }

        $objects = array();
        foreach ($data as $uid => $entry) {
            if (!empty($vars['required_group'])) {
                if (!$required_group->exists() || !$required_group->isMember($uid)) {
                    continue;
                }
            }
            $objects[$uid] = &Horde_Kolab_Server_Object::factory($type, $uid,
                                                                 $this, $entry);
        }
        return $objects;
    }

}