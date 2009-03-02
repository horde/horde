<?php
/**
 * The driver for accessing the Kolab user database stored in LDAP.
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
 * the standard Kolab LDAP db.
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
class Horde_Kolab_Server_ldap extends Horde_Kolab_Server
{

    /**
     * LDAP connection handle.
     *
     * @var Net_LDAP2
     */
    private $_ldap;

    /**
     * Base DN of the LDAP server.
     *
     * @var string
     */
    private $_base_dn;

    /**
     * Construct a new Horde_Kolab_Server_ldap object.
     *
     * @param array $params Parameter array.
     */
    public function __construct($params = array())
    {
        $base_config = array('host'           => 'localhost',
                             'port'           => 389,
                             'version'        => 3,
                             'starttls'       => true,
                             'uid'            => '',
                             'password'       => '',
                             'basedn'         => '',
                             'charset'        => '',
                             'options'        => array(),
                             'auto_reconnect' => true);


        $config = array_merge($base_config, $params);

        $this->_base_dn = $config['basedn'];

        $config['binddn'] = $config['uid'];
        $config['bindpw'] = $config['password'];

        $this->_ldap = new Net_LDAP2($config);

        parent::__construct($params);
    }

    /**
     * Read object data.
     *
     * @param string $dn    The object to retrieve.
     * @param string $attrs Restrict to these attributes.
     *
     * @return array|boolean An array of attributes or false if the specified
     *                       object was not found.
     *
     * @throws Horde_Kolab_Server_Exception If the search operation retrieved a
     *                                      problematic result.
     */
    public function read($dn, $attrs = null)
    {
        $params = array('scope' => 'one');
        if (isset($attrs)) {
            $params['attributes'] = $attr;
        }

        $result = $this->search(null, $params, $dn);
        if (empty($result) || !($result instanceOf Net_LDAP2_Search)) {
            throw new Horde_Kolab_Server_Exception(_("Empty or invalid result!"));
        }            

        $data = $result->as_struct();
        if (is_a($data, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($data);
        }
        if (!isset($data[$dn])) {
            throw new Horde_Kolab_Server_Exception(sprintf(_("No result found for %s"),
                                                           $dn));
        }
        if (is_a($data[$dn], 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($data[$dn]);
        }
        return $data[$dn];
    }

    /**
     * Determine the type of a Kolab object.
     *
     * @param string $dn The DN of the object to examine.
     *
     * @return int The corresponding Kolab object type.
     *
     * @throws Horde_Kolab_Server_Exception If the object type is unknown.
     */
    public function determineType($dn)
    {
        $oc = $this->getObjectClasses($dn);
        // Not a user type?
        if (!in_array('kolabinetorgperson', $oc)) {
            // Is it a group?
            if (in_array('kolabgroupofnames', $oc)) {
                return 'Horde_Kolab_Server_Object_group';
            }
            // Is it a shared Folder?
            if (in_array('kolabsharedfolder', $oc)) {
                return 'Horde_Kolab_Server_Object_sharedfolder';
            }
            throw new Horde_Kolab_Server_Exception(sprintf(_("Unkown Kolab object type for DN %s."),
                                                           $dn));
        }

        $groups = $this->getGroups($dn);
        if (!empty($groups)) {
            if (in_array('cn=admin,cn=internal,' . $this->_base_dn, $groups)) {
                return 'Horde_Kolab_Server_Object_administrator';
            }
            if (in_array('cn=maintainer,cn=internal,' . $this->_base_dn,
                         $groups)) {
                return 'Horde_Kolab_Server_Object_maintainer';
            }
            if (in_array('cn=domain-maintainer,cn=internal,' . $this->_base_dn,
                         $groups)) {
                return 'Horde_Kolab_Server_Object_domainmaintainer';
            }
        }

        if (strpos($dn, 'cn=external') !== false) {
            return 'Horde_Kolab_Server_Object_address';
        }

        return 'Horde_Kolab_Server_Object_user';
    }

    /**
     * List all objects of a specific type
     *
     * @param string $type   The type of the objects to be listed
     * @param array  $params Additional parameters.
     *
     * @return array An array of Kolab objects.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function listObjects($type, $params = null)
    {
        if (empty($params['base_dn'])) {
            $base = $this->_base_dn;
        } else {
            $base = $params['base_dn'];
        }

        $result = Horde_Kolab_Server_Object::loadClass($type);
        $vars   = get_class_vars($type);
        $filter = $vars['filter'];
        $sort   = $vars['sort_by'];

        if (isset($params['sort'])) {
            $sort = $params['sort'];
        }

        $options = array('scope' => 'sub');
        if (isset($params['attributes'])) {
            $options['attributes'] = $params['attributes'];
        } else {
            $options['attributes'] = $vars['_supported_attributes'];
        }

        $result = $this->search($filter, $options, $base);
        if (empty($result)) {
            return array();
        }

        if ($sort) {
/*             $this->sort($result, $sort); */
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

/*         $entries = $this->_getDns($result, $from, $to); */
/*         if (!$entries  && $this->_errno()) { */
/*             return PEAR::raiseError(sprintf(_("Search failed. Error was: %s"), */
/*                                             $this->_error())); */
/*         } */
/*         if (!$entries) { */
/*             return false; */
/*         } */

        $entries = array();
        foreach ($result as $entry) {
            $entries[] = $entry['dn'];
        }

        if (!empty($vars['required_group'])) {
            $required_group = $this->fetch($vars['required_group'],
                                           'Horde_Kolab_Server_Object_group');
        }

        $objects = array();
        foreach ($entries as $dn) {
            if (!empty($vars['required_group']) && $required_group->isMember($dn)) {
                continue;
            }
            $result = $this->fetch($dn, $type);
            $objects[] = $result;
        }
        return $objects;
    }

    /**
     * Generates a UID for the given information.
     *
     * @param string $type The type of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string The DN.
     *
     * @throws Horde_Kolab_Server_Exception If the given type is unknown.
     */
    public function generateServerUid($type, $id, $info)
    {
        switch ($type) {
        case 'Horde_Kolab_Server_Object_user':
            if (!isset($info['user_type']) || $info['user_type'] == 0) {
                return sprintf('cn=%s,%s', $id, $this->_base_dn);
            } else if ($info['user_type'] == KOLAB_UT_INTERNAL) {
                return sprintf('cn=%s,cn=internal,%s', $id, $this->_base_dn);
            } else if ($info['user_type'] == KOLAB_UT_GROUP) {
                return sprintf('cn=%s,cn=groups,%s', $id, $this->_base_dn);
            } else if ($info['user_type'] == KOLAB_UT_RESOURCE) {
                return sprintf('cn=%s,cn=resources,%s', $id, $this->_base_dn);
            } else {
                return sprintf('cn=%s,%s', $id, $this->_base_dn);
            }
        case 'Horde_Kolab_Server_Object_address':
            return sprintf('cn=%s,cn=external,%s', $id, $this->_base_dn);
        case 'Horde_Kolab_Server_Object_sharedfolder':
        case 'Horde_Kolab_Server_Object_administrator':
        case 'Horde_Kolab_Server_Object_maintainer':
        case 'Horde_Kolab_Server_Object_domainmaintainer':
            return sprintf('cn=%s,%s', $id, $this->_base_dn);
        case 'Horde_Kolab_Server_Object_group':
        case 'Horde_Kolab_Server_Object_distlist':
            if (!isset($info['visible']) || !empty($info['visible'])) {
                return sprintf('cn=%s,%s', $id, $this->_base_dn);
            } else {
                return sprintf('cn=%s,cn=internal,%s', $id, $this->_base_dn);
            }
        default:
            throw new Horde_Kolab_Server_Exception(_("Not implemented!"));
        }
    }

    /**
     * Return the root of the UID values on this server.
     *
     * @return string The base UID on this server (base DN on ldap).
     */
    public function getBaseUid()
    {
        return $this->_base_dn;
    }

    /**
     * Save an object.
     *
     * @param string $dn   The DN of the object.
     * @param array  $data The data for the object.
     *
     * @return boolean True if successfull.
     *
     * @throws Horde_Kolab_Server_Exception If the given type is unknown.
     */
    function save($dn, $data)
    {
        $result = $this->_add($dn, $data);
        if (!$result  && $this->_errno()) {
            throw new Horde_Kolab_Server_Exception(sprintf(_("Failed saving object. Error was: %s"),
                                                           $this->_error()));
        }
    }

    /**
     * Identify the UID for the first object found using the specified
     * search criteria.
     *
     * @param array $criteria The search parameters as array.
     * @param int   $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return boolean|string|array The UID(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function uidForSearch($criteria,
                                 $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        $users = array('field' => 'objectClass',
                       'op'    => '=',
                       'test'  => KOLAB_SERVER_USER);
        if (!empty($criteria)) {
            $criteria = array('AND' => array($users, $criteria));
        } else {
            $criteria = array('AND' => array($users));
        }

        $filter  = $this->searchQuery($criteria);
        return $this->dnForFilter($filter, $restrict);
    }

    /**
     * Identify the GID for the first group found using the specified
     * search criteria
     *
     * @param array $criteria The search parameters as array.
     * @param int   $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return boolean|string|array The GID(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function gidForSearch($criteria,
                                 $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        $groups = array('field' => 'objectClass',
                        'op'    => '=',
                        'test'  => KOLAB_SERVER_GROUP);
        if (!empty($criteria)) {
            $criteria = array('AND' => array($groups, $criteria));
        } else {
            $criteria = array('AND' => array($groups));
        }

        $filter  = $this->searchQuery($criteria);
        return $this->dnForFilter($filter, $restrict);
    }

    /**
     * Identify attributes for the objects found using a filter.
     *
     * @param array $criteria The search parameters as array.
     * @param array $attrs    The attributes to retrieve.
     * @param int   $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return array The results.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function attrsForSearch($criteria, $attrs,
                                   $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        $params = array('attributes' => $attrs);
        $filter = $this->searchQuery($criteria);
        $result = $this->search($filter, $params, $this->_base_dn);
        return $this->attrsFromResult($result, $attrs, $restrict);
    }

    /**
     * Search for object data.
     *
     * @param string $filter The LDAP search filter.
     * @param string $params Additional search parameters.
     * @param string $base   The search base
     *
     * @return array The result array.
     *
     * @throws Horde_Kolab_Server_Exception If the search operation encountered
     *                                      a problem.
     */
    public function search($filter = null, $params = array(), $base = null)
    {
        if (!isset($base)) {
            $base = $this->_base_dn;
        }
        $result = $this->_ldap->search($base, $filter, $params);
        if (is_a($result, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($result->getMessage());
        }
        return $result;
    }

    /**
     * Get the LDAP object classes for the given DN.
     *
     * @param string $dn DN of the object.
     *
     * @return array An array of object classes.
     *
     * @throws Horde_Kolab_Server_Exception If the object has no
     *                                      object classes.
     */
    public function getObjectClasses($dn)
    {
        $object = $this->read($dn, array('objectClass'));
        if (!isset($object['objectClass'])) {
            throw new Horde_Kolab_Server_Exception(sprintf(_("The object %s has no object classes!"),
                                                           $dn));
        }
        $result = array_map('strtolower', $object['objectClass']);
        return $result;
    }

    /**
     * Build a search query.
     *
     * Taken from the Turba LDAP driver.
     *
     * @param array $criteria The array of criteria.
     *
     * @return string  An LDAP query filter.
     */
    protected function searchQuery($criteria)
    {
        /* Build the LDAP filter. */
        $filter = '';
        if (count($criteria)) {
            foreach ($criteria as $key => $vals) {
                if ($key == 'OR') {
                    $filter .= '(|' . $this->buildSearchQuery($vals) . ')';
                } elseif ($key == 'AND') {
                    $filter .= '(&' . $this->buildSearchQuery($vals) . ')';
                }
            }
        } else {
            /* Accept everything. */
            $filter = '(objectclass=*)';
        }

        /* Add source-wide filters, which are _always_ AND-ed. */
        if (!empty($this->params['filter'])) {
            $filter = '(&' . '(' . $this->params['filter'] . ')' . $filter . ')';
        }
        return $filter;
    }

    /**
     * Build a piece of a search query.
     *
     * Taken from the Turba LDAP driver.
     *
     * @param array $criteria The array of criteria.
     *
     * @return string  An LDAP query fragment.
     */
    protected function buildSearchQuery($criteria)
    {
        $clause = '';
        foreach ($criteria as $key => $vals) {
            if (!empty($vals['OR'])) {
                $clause .= '(|' . $this->buildSearchQuery($vals) . ')';
            } elseif (!empty($vals['AND'])) {
                $clause .= '(&' . $this->buildSearchQuery($vals) . ')';
            } else {
                if (isset($vals['field'])) {
                    require_once 'Horde/String.php';
                    require_once 'Horde/NLS.php';
                    $rhs     = String::convertCharset($vals['test'], NLS::getCharset(), $this->params['charset']);
                    $clause .= Horde_LDAP::buildClause($vals['field'], $vals['op'], $rhs, array('begin' => !empty($vals['begin'])));
                } else {
                    foreach ($vals as $test) {
                        if (!empty($test['OR'])) {
                            $clause .= '(|' . $this->buildSearchQuery($test) . ')';
                        } elseif (!empty($test['AND'])) {
                            $clause .= '(&' . $this->buildSearchQuery($test) . ')';
                        } else {
                            $rhs     = String::convertCharset($test['test'], NLS::getCharset(), $this->params['charset']);
                            $clause .= Horde_LDAP::buildClause($test['field'], $test['op'], $rhs, array('begin' => !empty($vals['begin'])));
                        }
                    }
                }
            }
        }

        return $clause;
    }

    /**
     * Identify the DN of the first result entry.
     *
     * @param array $result   The LDAP search result.
     * @param int   $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return boolean|string|array The DN(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception If the number of results did not
     *                                      meet the expectations.
     */
    protected function dnFromResult($result,
                                    $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        if (empty($result)) {
            return false;
        }
        $dns = array();
        foreach ($result as $entry) {
            $dns[] = $entry['dn'];
        }

        switch ($restrict) {
        case KOLAB_SERVER_RESULT_STRICT:
            if (count($dns) > 1) {
                throw new Horde_Kolab_Server_Exception(sprintf(_("Found %s results when expecting only one!"),
                                                               $count));
            }
        case KOLAB_SERVER_RESULT_SINGLE:
            return $dns[0];
        case KOLAB_SERVER_RESULT_MANY:
            return $dns;
        }
    }

    /**
     * Get the attributes of the first result entry.
     *
     * @param array $result   The LDAP search result.
     * @param array $attrs    The attributes to retrieve.
     * @param int   $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return array The DN.
     *
     * @throws Horde_Kolab_Server_Exception If the number of results did not
     *                                      meet the expectations.
     */
    protected function attrsFromResult($result, $attrs,
                                       $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        switch ($restrict) {
        case KOLAB_SERVER_RESULT_STRICT:
            if (count($result) > 1) {
                throw new Horde_Kolab_Server_Exception(sprintf(_("Found %s results when expecting only one!"),
                                                               $count));
            }
        case KOLAB_SERVER_RESULT_SINGLE:
            if (count($result) > 0) {
                return $result[0];
            }
            return array();
        case KOLAB_SERVER_RESULT_MANY:
            return $result;
        }
        return array();
    }


    /**
     * Identify the DN for the first object found using a filter.
     *
     * @param string $filter   The LDAP filter to use.
     * @param int    $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return boolean|string|array The DN(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    protected function dnForFilter($filter,
                                   $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        $params = array('attributes' => 'dn');
        $result = $this->search($filter, $params, $this->_base_dn);
        return $this->dnFromResult($result, $restrict);
    }
}
