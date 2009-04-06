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
    /** Maximum accepted level for the object class hierarchy */
    const MAX_HIERARCHY = 100;

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
     * Does this server type support automatic schema analysis?
     *
     * @var boolean
     */
    public $schema_support = false;

    /**
     * The LDAP schemas.
     *
     * @var Net_LDAP2_Schema
     */
    private $_schema;

    /**
     * A cache for object attribute definitions.
     *
     * @var array
     */
    static protected $attributes;

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

        if (!empty($params['schema_support'])) {
            $this->schema_support = true;
        }

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
            throw new Horde_Kolab_Server_Exception($this->_ldap);
        }
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
        $params = array('scope' => 'base');
        if (!empty($attrs)) {
            $params['attributes'] = $attrs;
        }

        $result = $this->search(null, $params, $dn);
        $data   = $result->as_struct();
        if (is_a($data, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($data->getMessage());
        }
        if (empty($data)) {
            throw new Horde_Kolab_Server_Exception(_("Empty result!"));
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
     * Save an object.
     *
     * @param string  $dn     The DN of the object to be added.
     * @param array   $data   The attributes of the object to be added.
     * @param boolean $exists Does the object already exist on the server?
     *
     * @return boolean  True if saving succeeded.
     */
    public function save($dn, $data, $exists = false)
    {
        if ($exists === false) {
            $entry  = Net_LDAP2_Entry::createFresh($dn, $data);
            $result = $this->_ldap->add($entry);
            if ($result instanceOf PEAR_Error) {
                throw new Horde_Kolab_Server_Exception($result->getMessage());
            }
        } else {
            $deletes = array();
            foreach ($data as $key => $values) {
                $empty = true;
                foreach ($values as $value) {
                    if (!empty($value)) {
                        $empty = false;
                        break;
                    }
                }
                if ($empty === true) {
                    $deletes[] = $key;
                    unset($data[$key]);
                }
            }
            $result = $this->_ldap->modify($dn, array('delete' => $deletes,
                                                      'replace' => $data));
            if ($result instanceOf PEAR_Error) {
                throw new Horde_Kolab_Server_Exception($result->getMessage());
            }
        }
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

        $result = Horde_Kolab_Server_Object::loadClass($type);
        $vars   = get_class_vars($type);
        $filter = call_user_func(array($type, 'getFilter'));
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
        $data   = $result->as_struct();
        if (is_a($data, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($data->getMessage());
        }
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
     * Return the attributes supported by the given object class.
     *
     * @param string $class Determine the attributes for this class.
     *
     * @return array The supported attributes.
     *
     * @throws Horde_Kolab_Server_Exception If the schema analysis fails.
     */
    public function &getAttributes($class)
    {
        static $cache = null;
        static $lifetime;

        if (!isset($this->attributes)) {
            if (!empty($GLOBALS['conf']['kolab']['server']['cache']['driver'])) {
                $params = isset($GLOBALS['conf']['kolab']['server']['cache']['params'])
                    ? $GLOBALS['conf']['kolab']['server']['cache']['params'] : null;
                $params['sub'] = 'attributes';
                $cache = Horde_Cache::singleton($GLOBALS['conf']['kolab']['server']['cache']['driver'],
                                                $params);
                register_shutdown_function(array($this, 'shutdown'));
                $lifetime = isset($GLOBALS['conf']['kolab']['server']['cache']['lifetime'])
                    ? $GLOBALS['conf']['kolab']['server']['cache']['lifetime'] : 300;
            }
        }

        if (empty($this->attributes[$class])) {

            if (!empty($cache)) {
                $this->attributes[$class] = @unserialize($cache->get($class, $lifetime));
            }

            if (empty($this->attributes[$class])) {

                $childclass = $class;
                $classes    = array();
                $level      = 0;
                while ($childclass != 'Horde_Kolab_Server_Object'
                       && $level < self::MAX_HIERARCHY) {
                    $classes[] = $childclass;
                    $childclass = get_parent_class($childclass);
                    $level++;
                }

                /** Finally add the basic object class */
                $classes[] = $childclass;

                if ($level == self::MAX_HIERARCHY) {
                    Horde::logMessage(sprintf('The maximal level of the object hierarchy has been exceeded for class \"%s\"!',
                                              $class),
                                      __FILE__, __LINE__, PEAR_LOG_ERROR);
                }

                /**
                 * Collect attributes from top to bottom.
                 */
                $classes = array_reverse($classes);

                $derived        = array();
                $defaults       = array();
                $locked         = array();
                $object_classes = array();

                foreach ($classes as $childclass) {
                    $vars = get_class_vars($childclass);
                    if (isset($vars['init_attributes'])) {
                        $derived = array_merge($derived,
                                               $vars['init_attributes']['derived']);
                        $defaults = array_merge($defaults,
                                                $vars['init_attributes']['defaults']);
                        $locked = array_merge($locked,
                                              $vars['init_attributes']['locked']);
                        $object_classes = array_merge($object_classes,
                                                      $vars['init_attributes']['object_classes']);
                    }
                }

                if ($this->schema_support === true) {
                    if (!isset($this->_schema)) {
                        $result = $this->_ldap->schema();
                        if ($result instanceOf PEAR_Error) {
                            throw new Horde_Kolab_Server_Exception($result->getMessage());
                        }
                        $this->_schema = &$result;
                    }
                    $supported = array();
                    $required  = array();
                    foreach ($object_classes as $object_class) {
                        $info = $this->_schema->get('objectclass', $object_class);
                        if ($info instanceOf PEAR_Error) {
                            throw new Horde_Kolab_Server_Exception($info->getMessage());
                        }
                        if (isset($info['may'])) {
                            $supported = array_merge($supported, $info['may']);
                        }
                        if (isset($info['must'])) {
                            $supported = array_merge($supported, $info['must']);
                            $required  = array_merge($required, $info['must']);
                        }
                    }
                    if (empty($supported) && empty($required)) {
                        return false;
                    }
                    foreach ($supported as $attribute) {
                        $info = $this->_schema->get('attribute', $attribute);
                        if ($info instanceOf PEAR_Error) {
                            throw new Horde_Kolab_Server_Exception($info->getMessage());
                        }
                        $attrs[$attribute] = $info;
                    }
                    foreach ($required as $attribute) {
                        $attrs[$attribute]['required'] = true;
                    }
                    foreach ($locked as $attribute) {
                        $attrs[$attribute]['locked'] = true;
                    }
                    foreach ($defaults as $attribute) {
                        $attrs[$attribute]['default'] = true;
                    }
                    $attrs[Horde_Kolab_Server_Object::ATTRIBUTE_OC]['default'] = $object_classes;

                    $attrs = array_merge($attrs, $derived);

                    $this->attributes[$class] = array($attrs,
                                                      array(
                                                          'derived'  => $derived,
                                                          'locked'   => $locked,
                                                          'required' => $required,
                                                      ));
                } else {
                    $this->attributes[$class] = array(array(),
                                                      array(
                                                          'derived'  => array(),
                                                          'locked'   => array(),
                                                          'required' => array(),
                                                      ));
                }
            }
        }
        return $this->attributes[$class];
    }

    /**
     * Stores the attribute definitions in the cache.
     */
    function shutdown()
    {
        if (isset($this->attributes)) {
            if (!empty($GLOBALS['conf']['kolab']['server']['cache']['driver'])) {
                $params = isset($GLOBALS['conf']['kolab']['server']['cache']['params'])
                    ? $GLOBALS['conf']['kolab']['server']['cache']['params'] : null;
                $params['sub'] = 'attributes';
                $cache = Horde_Cache::singleton($GLOBALS['conf']['kolab']['server']['cache']['driver'],
                                                $params);
                foreach ($this->attributes as $key => $value) {
                    $cache->set($key, @serialize($value));
                }
            }
        }
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
        $object = $this->read($dn, array(Horde_Kolab_Server_Object::ATTRIBUTE_OC));
        if (!isset($object[Horde_Kolab_Server_Object::ATTRIBUTE_OC])) {
            throw new Horde_Kolab_Server_Exception(sprintf(_("The object %s has no %s attribute!"),
                                                           $dn, Horde_Kolab_Server_Object::ATTRIBUTE_OC));
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
            $filter = '(' . strtolower(Horde_Kolab_Server_Object::ATTRIBUTE_OC) . '=*)';
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
                    $rhs     = String::convertCharset($vals['test'],
                                                      NLS::getCharset(),
                                                      $this->params['charset']);
                    $clause .= Horde_LDAP::buildClause($vals['field'],
                                                       $vals['op'], $rhs,
                                                       array('begin' => !empty($vals['begin'])));
                } else {
                    foreach ($vals as $test) {
                        if (!empty($test['OR'])) {
                            $clause .= '(|' . $this->buildSearchQuery($test) . ')';
                        } elseif (!empty($test['AND'])) {
                            $clause .= '(&' . $this->buildSearchQuery($test) . ')';
                        } else {
                            $rhs     = String::convertCharset($test['test'],
                                                              NLS::getCharset(),
                                                              $this->params['charset']);
                            $clause .= Horde_LDAP::buildClause($test['field'],
                                                               $test['op'], $rhs,
                                                               array('begin' => !empty($vals['begin'])));
                        }
                    }
                }
            }
        }

        return $clause;
    }

    /**
     * Determine the type of a Kolab object.
     *
     * @param string $uid The UID of the object to examine.
     *
     * @return int The corresponding Kolab object type.
     *
     * @throws Horde_Kolab_Server_Exception If the object type is unknown.
     */
    public function determineType($uid)
    {
        $ocs = $this->getObjectClasses($uid);
        array_reverse($ocs);
        foreach ($ocs as $oc) {
            try {
                Horde_Kolab_Server_Object::loadClass($oc);
                return $oc;
            } catch (Horde_Kolab_Server_Exception $e)  {
            }
        }
        throw new Horde_Kolab_Server_Exception(sprintf(_("Unkown Kolab object type for UID %s."),
                                                       $uid));
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
        default:
            Horde_Kolab_Server_Object::loadClass($type);
            call_user_func(array($type, 'generateServerUid'), $id, $info);
        }
    }
}
