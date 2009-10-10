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
abstract class Horde_Kolab_Server_Ldap implements Horde_Kolab_Server
{
    /**
     * The GUID of the current user.
     *
     * @var string
     */
    private $_guid;

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
        Horde_Kolab_Server_Connection $connection,
        $base_dn,
        $filter = null
    ) {
        $this->_conn    = $connection;
        $this->_base_dn = $base_dn;
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
    public function connectGuid($guid = null, $pass = null)
    {
        /** Do we need to switch the user? */
        if ((empty($guid) && empty($this->_guid))
            || $guid !== $this->_guid
        ) {
            $this->_handleError(
                $this->_conn->getRead()->bind($guid, $pass),
                Horde_Kolab_Server_Exception::BIND_FAILED
            );
            $this->_guid = $guid;
        }
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
        $data = $this->_search(null, $params, $guid)->asArray();
        if ($data->count() == 0) {
            throw new Horde_Kolab_Server_Exception(
                'Empty result!',
                Horde_Kolab_Server_Exception::EMPTY_RESULT
            );
        }            
        return array_pop($data->asArray());
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
        return array_pop($data->asArray());
    }

    /**
     * Finds object data matching a given set of criteria.
     *
     * @param Horde_Kolab_Server_Query $query  The criteria for the search.
     * @param array                    $params Additional search parameters.
     *
     * @return Horde_Kolab_Server_Result The result object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function find(
        Horde_Kolab_Server_Query $query,
        array $params = array()
    ) {
        $this->findBelow($query, $this->_base_dn, $params);
    }

    /**
     * Finds all object data below a parent matching a given set of criteria.
     *
     * @param Horde_Kolab_Server_Query $query  The criteria for the search.
     * @param string                   $parent The parent to search below.
     * @param array                    $params Additional search parameters.
     *
     * @return Horde_Kolab_Server_Result The result object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    abstract public function findBelow(
        Horde_Kolab_Server_Query $query,
        $parent,
        array $params = array()
    );

    /**
     * Modify existing object data.
     *
     * @param string $guid The GUID of the object to be added.
     * @param array  $data The attributes of the object to be stored.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function save($guid, array $data)
    {
        $entry  = $this->_conn->getWrite()->getEntry($guid, $data['attributes']);
        $this->_handleError(
            $this->_conn->getWrite()->modify($entry, $data),
            Horde_Kolab_Server_Exception::SYSTEM
        );
    }

    /**
     * Add new object data.
     *
     * @param string $guid The GUID of the object to be added.
     * @param array  $data The attributes of the object to be added.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function add($guid, array $data)
    {
        $entry  = Net_LDAP2_Entry::createFresh($guid, $data);
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
            $this->_conn->getWrite()->move($old, $new),
            Horde_Kolab_Server_Exception::SYSTEM
        );
    }

    /**
     * Return the ldap schema.
     *
     * @return Net_LDAP2_Schema The LDAP schema.
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
        if (is_a($result, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($result, $code);
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
    private function _search($filter, array $params, $base)
    {
        $this->_lastSearch = &$this->_conn->getRead()->search(
            $base, $filter, $params
        );
        $this->_handleError(
            $this->_lastSearch, Horde_Kolab_Server_Exception::SYSTEM
        );
        return new Horde_Kolab_Server_Result_Ldap($this->_lastSearch);
    }
}
