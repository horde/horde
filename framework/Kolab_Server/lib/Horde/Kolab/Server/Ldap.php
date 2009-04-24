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
class Horde_Kolab_Server_Ldap extends Horde_Kolab_Server
{
    /**
     * LDAP connection handle.
     *
     * @var Net_LDAP2
     */
    private $_ldap;

    /**
     * The configuration for connection to the LDAP server.
     *
     * @var array
     */
    private $_config;

    /**
     * Base DN of the LDAP server.
     *
     * @var string
     */
    private $_base_dn;

    /**
     * The LDAP schemas.
     *
     * @var Net_LDAP2_Schema
     */
    private $_schema;

    /**
     * Construct a new Horde_Kolab_Server_ldap object.
     *
     * @param array $params Parameter array.
     */
    public function __construct($params = array())
    {
        if (!isset($params['charset'])) {
            $params['charset'] = 'UTF-8';
        }

        $base_config = array('host'           => 'localhost',
                             'port'           => 389,
                             'version'        => 3,
                             'starttls'       => false,
                             'uid'            => '',
                             'pass'           => '',
                             'basedn'         => '',
                             'charset'        => '',
                             'options'        => array(),
                             'auto_reconnect' => true);

        $config = array_merge($base_config, $params);

        $this->_base_dn = $config['basedn'];

        $config['binddn'] = $config['uid'];
        $config['bindpw'] = $config['pass'];

        $this->_config = $config;

        $this->connect();

        parent::__construct($params);
    }

    
    /**
     * Connect to the LDAP server.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the connection failed.
     */
    protected function connect()
    {
        $this->_ldap = Net_LDAP2::connect($this->_config);
        if (is_a($this->_ldap, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($this->_ldap,
                                                   Horde_Kolab_Server_Exception::SYSTEM);
        }
    }

    /**
     * Map attributes defined within this library their their real world
     * counterparts.
     *
     * @param array $data The data that has been read and needs to be mapped.
     *
     * @return NULL
     */
    protected function unmapAttributes(&$data)
    {
        if (!empty($this->params['map'])) {
            foreach ($this->params['map'] as $attribute => $map) {
                if (isset($data[$map])) {
                    $data[$attribute] = $data[$map];
                    unset($data[$map]);
                }
            }
        }
    }

    /**
     * Map attributes defined within this library into their real world
     * counterparts.
     *
     * @param array $data The data to be written.
     *
     * @return NULL
     */
    protected function mapAttributes(&$data)
    {
        if (!empty($this->params['map'])) {
            foreach ($this->params['map'] as $attribute => $map) {
                if (isset($data[$attribute])) {
                    $data[$map] = $data[$attribute];
                    unset($data[$attribute]);
                }
            }
        }
    }

    /**
     * Map attribute keys defined within this library into their real world
     * counterparts.
     *
     * @param array $keys The attribute keys.
     *
     * @return NULL
     */
    protected function mapKeys(&$keys)
    {
        if (!empty($this->params['map'])) {
            foreach ($this->params['map'] as $attribute => $map) {
                $key = array_search($attribute, $keys);
                if ($key !== false) {
                    $keys[$key] = $map;
                }
            }
        }
    }

    /**
     * Map a single attribute key defined within this library into its real
     * world counterpart.
     *
     * @param array $field The attribute name.
     *
     * @return The real name of this attribute on the server we connect to.
     */
    protected function mapField($field)
    {
        if (!empty($this->params['map'])
            && isset($this->params['map'][$field])) {
            return $this->params['map'][$field];
        }
        return $field;
    }

    /**
     * Low level access to reading object data.
     *
     * This function provides fast access to the Server data.
     *
     * Usually you should use
     *
     * $object = $server->fetch('a server uid');
     * $variable = $object['attribute']
     *
     * to access object attributes. This is slower but takes special object
     * handling into account (e.g. custom attribute parsing).
     *
     * @param string $uid   The object to retrieve.
     * @param string $attrs Restrict to these attributes.
     *
     * @return array|boolean An array of attributes or false if the specified
     *                       object was not found.
     *
     * @throws Horde_Kolab_Server_Exception If the search operation retrieved a
     *                                      problematic result.
     */
    public function read($uid, $attrs = null)
    {
        $params = array('scope' => 'base');
        if (!empty($attrs)) {
            $params['attributes'] = $attrs;
        }

        $data = $this->search(null, $params, $uid);
        if (empty($data)) {
            throw new Horde_Kolab_Server_Exception(_("Empty result!"),
                                                   Horde_Kolab_Server_Exception::EMPTY_RESULT);
        }            

        return array_pop($data);
    }

    /**
     * Save an object.
     *
     * @param string  $uid    The UID of the object to be added.
     * @param array   $data   The attributes of the object to be added.
     * @param boolean $exists Does the object already exist on the server?
     *
     * @return boolean  True if saving succeeded.
     */
    public function save($uid, $data, $exists = false)
    {
        $this->mapAttributes($data);

        if ($exists === false) {
            $entry  = Net_LDAP2_Entry::createFresh($uid, $data);
            $result = $this->_ldap->add($entry);
            if ($result instanceOf PEAR_Error) {
                throw new Horde_Kolab_Server_Exception($result,
                                                       Horde_Kolab_Server_Exception::SYSTEM);
            }
        } else {
            $deletes = array();
            foreach ($data as $key => $values) {
                $empty = true;
                if (!is_array($values)) {
                    $values = array($values);
                }
                foreach ($values as $value) {
                    if (!($value === null || $info[$key] === '')) {
                        $empty = false;
                        break;
                    }
                }
                if ($empty === true) {
                    $deletes[] = $key;
                    unset($data[$key]);
                }
            }
            /* Net_LDAP2 will work on this as a reference */
            $mod_uid = $uid;
            $result = $this->_ldap->modify($mod_uid, array('delete' => $deletes,
                                                           'replace' => $data));
            if ($result instanceOf PEAR_Error) {
                throw new Horde_Kolab_Server_Exception($result,
                                                       Horde_Kolab_Server_Exception::SYSTEM);
            }
        }
        Horde::logMessage(sprintf('The object \"%s\" has been successfully saved!',
                                  $uid),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);
        return true;
    }

    /**
     * Delete an object.
     *
     * @param string $uid The UID of the object to be deleted.
     *
     * @return boolean True if saving succeeded.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function delete($uid)
    {
        $result = $this->_ldap->delete($uid);
        if ($result instanceOf PEAR_Error) {
            throw new Horde_Kolab_Server_Exception($result,
                                                   Horde_Kolab_Server_Exception::SYSTEM);
        }
        Horde::logMessage(sprintf('The object \"%s\" has been successfully deleted!',
                                  $uid),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);
        return true;
    }

    /**
     * Rename an object.
     *
     * @param string $uid The UID of the object to be renamed.
     * @param string $new The new UID of the object.
     *
     * @return boolean True if renaming succeeded.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function rename($uid, $new)
    {
        /* Net_LDAP modifies the variable */
        $old = $uid;
        $result = $this->_ldap->move($old, $new);
        if ($result instanceOf PEAR_Error) {
            throw new Horde_Kolab_Server_Exception($result,
                                                   Horde_Kolab_Server_Exception::SYSTEM);
        }
        Horde::logMessage(sprintf('The object \"%s\" has been successfully renamed to \"%s\"!',
                                  $uid, $new),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);
        return true;
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
            $options['attributes'] = $vars['_supported_attributes'];
        }

        $data = $this->search($filter, $options, $base);
        if (empty($data)) {
            return array();
        }

        if ($sort) {
            /* FIXME */
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
            $required_group = $this->fetch($vars['required_group'],
                                           'Horde_Kolab_Server_Object_Kolabgroupofnames');
        }

        $objects = array();
        foreach ($data as $uid => $entry) {
            if (!empty($vars['required_group']) && $required_group->isMember($uid)) {
                continue;
            }
            $result = &Horde_Kolab_Server_Object::factory($type, $uid,
                                                          $this, $entry);
            $objects[$uid] = $result;
        }
        return $objects;
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
     * Return the ldap schema.
     *
     * @return Net_LDAP2_Schema The LDAP schema.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    private function _getSchema()
    {
        if (!isset($this->_schema)) {
            $result = $this->_ldap->schema();
            if ($result instanceOf PEAR_Error) {
                throw new Horde_Kolab_Server_Exception($result,
                                                       Horde_Kolab_Server_Exception::SYSTEM);
            }
            $this->_schema = &$result;
        }
        return $this->_schema;
    }

    /**
     * Return the schema for the given objectClass.
     *
     * @param string $objectclass Fetch the schema for this objectClass.
     *
     * @return array The schema for the given objectClass.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    protected function getObjectclassSchema($objectclass)
    {
        if (!empty($this->_config['schema_support'])) {
            $schema = $this->_getSchema();
            $info = $schema->get('objectclass', $objectclass);
            if ($info instanceOf PEAR_Error) {
                throw new Horde_Kolab_Server_Exception($info,
                                                       Horde_Kolab_Server_Exception::SYSTEM);
            }
            return $info;
        }
        return parent::getObjectclassSchema($objectclass);
    }

    /**
     * Return the schema for the given attribute.
     *
     * @param string $attribute Fetch the schema for this attribute.
     *
     * @return array The schema for the given attribute.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    protected function getAttributeSchema($attribute)
    {
        if (!empty($this->_config['schema_support'])) {
            $schema = $this->_getSchema();
            $info = $schema->get('attribute', $attribute);
            if ($info instanceOf PEAR_Error) {
                throw new Horde_Kolab_Server_Exception($info,
                                                       Horde_Kolab_Server_Exception::SYSTEM);
            }
            return $info;
        }
        return parent::getAttributeSchema($attribute);
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
        if (isset($params['attributes'])) {
            $this->mapKeys($params['attributes']);
        }

        if (!isset($base)) {
            $base = $this->_base_dn;
        }
        $result = $this->_ldap->search($base, $filter, $params);
        if (is_a($result, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($result,
                                                   Horde_Kolab_Server_Exception::SYSTEM);
        }
        $data   = $result->as_struct();
        if (is_a($data, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($data,
                                                   Horde_Kolab_Server_Exception::SYSTEM);
        }
        $this->unmapAttributes($data);
        return $data;
    }

    /**
     * Get the LDAP object classes for the given DN.
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
            throw new Horde_Kolab_Server_Exception(sprintf("The object %s has no %s attribute!",
                                                           $uid, Horde_Kolab_Server_Object::ATTRIBUTE_OC),
                                                   Horde_Kolab_Server_Exception::SYSTEM);
        }
        $result = array_map('strtolower',
                            $object[Horde_Kolab_Server_Object::ATTRIBUTE_OC]);
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
    public function searchQuery($criteria)
    {
        /* Accept everything. */
        $filter = '(' . strtolower(Horde_Kolab_Server_Object::ATTRIBUTE_OC) . '=*)';

        /* Build the LDAP filter. */
        if (count($criteria)) {
            $f = $this->buildSearchQuery($criteria);
            if ($f instanceOf Net_LDAP2_Filter) {
                $filter = $f->asString();
            }
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
    protected function &buildSearchQuery($criteria)
    {
        if (isset($criteria['field'])) {
            require_once 'Horde/String.php';
            require_once 'Horde/NLS.php';
            $rhs     = $criteria['test'];
            /* Keep this in for reference as we did not really test servers with different encoding yet */
            //$rhs     = String::convertCharset($criteria['test'], NLS::getCharset(), $this->params['charset']);
            switch ($criteria['op']) {
            case '=':
                $op = 'equals';
                break;
            }
            return Net_LDAP2_Filter::create($this->mapField($criteria['field']),
                                            $op, $rhs);
        }
        foreach ($criteria as $key => $vals) {
            if (!empty($vals['OR'])
                || !empty($vals['AND'])
                || !empty($vals['NOT'])) {
                $parts = $this->buildSearchQuery($vals);
                if (count($parts) > 1) {
                    if (!empty($vals['OR'])) {
                        $operator = '|';
                    } else if (!empty($vals['NOT'])) {
                        $operator = '!';
                    } else {
                        $operator = '&';
                    }
                    return Net_LDAP2_Filter::combine($operator, $parts);
                } else {
                    return $parts[0];
                }
            } else {
                $parts = array();
                foreach ($vals as $test) {
                    $parts[] = &$this->buildSearchQuery($test);
                }
                switch ($key) {
                case 'OR':
                    $operator = '|';
                    break;
                case 'AND':
                    $operator = '&';
                    break;
                case 'NOT':
                    $operator = '!';
                    break;
                }
                if (count($parts) > 1) {
                    return Net_LDAP2_Filter::combine($operator, $parts);
                } else if ($operator == '!') {
                    return Net_LDAP2_Filter::combine($operator, $parts[0]);
                } else {
                    return $parts[0];
                }
            }
        }
    }
}
