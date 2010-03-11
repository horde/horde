<?php
/**
 * The driver for accessing objects stored in LDAP.
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
 * This class provides methods to deal with objects stored in
 * a LDAP db.
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
abstract class Horde_Kolab_Server_Ldap
implements Horde_Kolab_Server_Interface
{
    /**
     * The GUID of the current user.
     *
     * @var string|boolean
     */
    private $_guid = false;

    /**
     * LDAP connection handle.
     *
     * @var Horde_Kolab_Server_Connection
     */
    private $_conn;

    /**
     * Base DN of the LDAP server.
     *
     * @var string
     */
    private $_base_dn;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Server_Connection $connection The LDAP connection.
     * @param string                        $base_dn    The LDAP server base DN.
     * @param string                        $filter     A global filter to add
     *                                                  to all queries.
     */
    public function __construct(
        Horde_Kolab_Server_Connection_Interface $connection,
        $base_dn
    ) {
        $this->_conn    = $connection;
        $this->_base_dn = $base_dn;

        $this->_handleError(
            Horde_Ldap::checkLDAPExtension(),
            Horde_Kolab_Server_Exception::MISSING_LDAP_EXTENSION
        );
    }

    /**
     * Connect to the server.
     *
     * @param string $guid The global unique id of the user.
     * @param string $pass The password.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the connection failed.
     */
    public function connectGuid($guid = '', $pass = '')
    {
        /** Do we need to switch the user? */
        if ($guid !== $this->_guid) {
            try {
                $this->_conn->getRead()->bind($guid, $pass);
            } catch (Horde_Ldap_Exception $e) {
                if ($e->getCode() == 49) {
                    throw new Horde_Kolab_Server_Exception_Bindfailed(
                        'Invalid username/password!',
                        Horde_Kolab_Server_Exception::BIND_FAILED,
                        $e
                    );
                } else {
                    throw new Horde_Kolab_Server_Exception(
                        'Bind failed!',
                        Horde_Kolab_Server_Exception::BIND_FAILED,
                        $e
                    );
                }
            }
            $this->_guid = $guid;
        }
    }

    /**
     * Get the current GUID
     *
     * @return string The GUID of the connected user.
     */
    public function getGuid()
    {
        return $this->_guid;
    }

    /**
     * Get the base GUID of this server
     *
     * @return string The base GUID of this server.
     */
    public function getBaseGuid()
    {
        return $this->_base_dn;
    }

    /**
     * Low level access to reading object data.
     *
     * @param string $guid The object to retrieve.
     *
     * @return array An array of attributes.
     *
     * @throws Horde_Kolab_Server_Exception If the search operation hit an error
     *                                      or returned no result.
     */
    public function read($guid)
    {
        $params = array('scope' => 'base');
        $data = $this->_search(null, $params, $guid);
        if ($data->count() == 0) {
            throw new Horde_Kolab_Server_Exception(
                'Empty result!',
                Horde_Kolab_Server_Exception::EMPTY_RESULT
            );
        }
        $result = $data->asArray();
        return array_pop($result);
    }

    /**
     * Low level access to reading some object attributes.
     *
     * @param string $guid  The object to retrieve.
     * @param string $attrs Restrict to these attributes.
     *
     * @return array An array of attributes.
     *
     * @throws Horde_Kolab_Server_Exception If the search operation hit an error
     *                                      or returned no result.
     */
    public function readAttributes($guid, array $attrs)
    {
        $params = array(
            'scope' => 'base',
            'attributes' => $attrs
        );
        $data = $this->_search(null, $params, $guid);
        if ($data->count() == 0) {
            throw new Horde_Kolab_Server_Exception(
                'Empty result!',
                Horde_Kolab_Server_Exception::EMPTY_RESULT
            );
        }
        $result = $data->asArray();
        return array_pop($result);
    }

    /**
     * Finds object data matching a given set of criteria.
     *
     * @param string $query  The LDAP search query
     * @param array  $params Additional search parameters.
     *
     * @return Horde_Kolab_Server_Result The result object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function find($query, array $params = array())
    {
        return $this->findBelow($query, $this->_base_dn, $params);
    }

    /**
     * Modify existing object data.
     *
     * @param Horde_Kolab_Server_Object $object The object to be modified.
     * @param array                     $data   The attributes of the object
     *                                          to be stored.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function save(
        Horde_Kolab_Server_Object_Interface $object,
        array $data
    ) {
        $changes = new Horde_Kolab_Server_Ldap_Changes($object, $data);
        $entry  = $this->_conn->getWrite()->getEntry(
            $object->getGuid(), array_keys($data)
        );
        $this->_handleError($entry, Horde_Kolab_Server_Exception::SYSTEM);
        $this->_handleError(
            $this->_conn->getWrite()->modify($entry, $changes->getChangeset()),
            Horde_Kolab_Server_Exception::SYSTEM
        );
    }

    /**
     * Add new object data.
     *
     * @param Horde_Kolab_Server_Object $object The object to be added.
     * @param array                     $data   The attributes of the object
     *                                          to be added.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function add(
        Horde_Kolab_Server_Object_Interface $object,
        array $data
    ) {
        $entry  = Horde_Ldap_Entry::createFresh($object->getGuid(), $data);
        $this->_handleError($entry, Horde_Kolab_Server_Exception::SYSTEM);
        $this->_handleError(
            $this->_conn->getWrite()->add($entry),
            Horde_Kolab_Server_Exception::SYSTEM
        );
    }

    /**
     * Delete an object.
     *
     * @param string $guid The UID of the object to be deleted.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function delete($guid)
    {
        $this->_handleError(
            $this->_conn->getWrite()->delete($guid),
            Horde_Kolab_Server_Exception::SYSTEM
        );
    }

    /**
     * Rename an object.
     *
     * @param string $guid The UID of the object to be renamed.
     * @param string $new  The new UID of the object.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function rename($guid, $new)
    {
        $this->_handleError(
            $this->_conn->getWrite()->move($guid, $new),
            Horde_Kolab_Server_Exception::SYSTEM
        );
    }

    /**
     * Return the ldap schema.
     *
     * @return Horde_Ldap_Schema The LDAP schema.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    public function getSchema()
    {
        $result = $this->_conn->getRead()->schema();
        $this->_handleError($result, Horde_Kolab_Server_Exception::SYSTEM);
        return $result;
    }

    /**
     * Get the parent GUID of this object.
     *
     * @param string $guid The GUID of the child.
     *
     * @return string the parent GUID of this object.
     */
    public function getParentGuid($guid)
    {
        $base = Horde_Ldap_Util::ldap_explode_dn(
            $guid,
            array(
                'casefold' => 'none',
                'reverse' => false,
                'onlyvalues' => false
            )
        );
        $this->_handleError($base);
        $id = array_shift($base);
        $parent = Horde_Ldap_Util::canonical_dn(
            $base, array('casefold' => 'none')
        );
        $this->_handleError($parent);
        return $parent;
    }

    /**
     * Check for a PEAR Error and convert it to an exception if necessary.
     *
     * @param mixed $result The result to be tested.
     * @param code  $code   The error code to use in case the result is an error.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the connection failed.
     */
    private function _handleError(
        $result,
        $code = Horde_Kolab_Server_Exception::SYSTEM
    ) {
        if ($result instanceOf PEAR_Error) {
            if ($code == Horde_Kolab_Server_Exception::BIND_FAILED
                && $result->getCode() == 49) {
                throw new Horde_Kolab_Server_Exception_Bindfailed($result, $code);
            } else {
                throw new Horde_Kolab_Server_Exception($result, $code);
            }
        }
    }

    /**
     * Search for object data.
     *
     * @param string $filter The LDAP search filter.
     * @param array  $params Additional search parameters.
     * @param string $base   The search base
     *
     * @return array The result array.
     *
     * @throws Horde_Kolab_Server_Exception If the search operation encountered
     *                                      a problem.
     */
    protected function _search($filter, array $params, $base)
    {
        $search = $this->_conn->getRead()->search($base, $filter, $params);
        $this->_handleError($search, Horde_Kolab_Server_Exception::SYSTEM);
        return new Horde_Kolab_Server_Result_Ldap($search);
    }
}
