<?php
/**
 * A driver for simulating a Kolab user database stored in LDAP.
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
 * This class provides a class for testing the Kolab Server DB.
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
class Horde_Kolab_Server_Ldap_Mock extends Horde_Kolab_Server_Ldap_Standard
{

    /**
     * The current database data.
     *
     * @var array
     */
    protected $data;

    /**
     * Indicates if we are bound.
     *
     * @var array
     */
    protected $bound;

    /**
     * Array holding the current result set.
     *
     * @var array
     */
    private $_result;

    /**
     * Buffer for error numbers.
     *
     * @var int
     */
    private $_errno = 0;

    /**
     * Buffer for error descriptions.
     *
     * @var int
     */
    private $_error = '';

    /**
     * Attribute used for sorting.
     *
     * @var string
     */
    private $_sort_by;

    /**
     * A result cache for iterating over the result.
     *
     * @var array
     */
    private $_current_result;

    /**
     * An index into the current result for iterating.
     *
     * @var int
     */
    private $_current_index;

    /**
     * Set configuration parameters.
     *
     * @param array $params The parameters.
     *
     * @return NULL
     */
    public function setParams(array $params)
    {
        //@todo Load when connecting
        //$this->load();

        if (isset($params['data'])) {
            $this->data = $params['data'];
        } else {
            if (!isset($this->data)) {
               $this->data  = array();
            }
        }

        if (isset($this->params['admin'])
            && isset($this->params['admin']['type'])) {
            $type = $this->params['admin']['type'];
            $data = $this->params['admin'];
            unset($data['type']);
            $admin = new $type($this, null, $data);
            if (!$admin->exists()) {
                $admin->save();
            }
        }

        //@todo Load when connecting
        //$this->store();

        parent::setParams($params);
    }

    /**
     * Connect to the LDAP server.
     *
     * @param string $uid  The unique id of the user.
     * @param string $pass The password.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the connection failed.
     */
    protected function _connectUid($uid = null, $pass = null)
    {
        //@todo
    }

    /**
     * Load the current state of the database.
     *
     * @return NULL
     */
    protected function load()
    {
        //@todo: remove the global
        if (isset($GLOBALS['KOLAB_SERVER_TEST_DATA'])) {
            $this->data = $GLOBALS['KOLAB_SERVER_TEST_DATA'];
        } else {
            $this->data = array();
        }
    }

    /**
     * Store the current state of the database.
     *
     * @return NULL
     */
    protected function store()
    {
        $GLOBALS['KOLAB_SERVER_TEST_DATA'] = $this->data;
    }

    /**
     * Cleans the current state of the database.
     *
     * @return NULL
     */
    public function clean()
    {
        $this->unbind();

        $GLOBALS['KOLAB_SERVER_TEST_DATA'] = array();

        $this->data = array();
    }

    /**
     * Binds the LDAP connection with a specific user and pass.
     *
     * @param string $dn DN to bind with
     * @param string $pw Password associated to this DN.
     *
     * @return boolean Whether or not the binding succeeded.
     *
     * @throws Horde_Kolab_Server_Exception If the user does not exit, he has no
     *                                      password, provided an incorrect
     *                                      password or anonymous binding is not
     *                                      allowed.
     */
    protected function bind($dn = false, $pw = '')
    {
        if (!$dn) {
            if (isset($this->params['uid'])) {
                $dn = $this->params['uid'];
            } else {
                $dn = '';
            }
        }
        if (!$pw) {
            if (isset($this->params['pass'])) {
                $pw = $this->params['pass'];
            }
        }

        if (!empty($dn)) {
            if (!isset($this->data[$dn])) {
                throw new Horde_Kolab_Server_Exception('User does not exist!');
            }

            $this->bound = true;

            try {
                $data = $this->read($dn, array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD));
            } catch (Horde_Kolab_Server_Exception $e) {
                $this->bound = false;
                throw $e;
            }
            if (!isset($data[Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD])) {
                $this->bound = false;
                throw new Horde_Kolab_Server_Exception('User has no password entry!');
            }
            $this->bound = $data['userPassword'][0] == $pw;
            if (!$this->bound) {
                throw new Horde_Kolab_Server_Exception('Incorrect password!');
            }
        } else if (!empty($this->params['no_anonymous_bind'])) {
            $this->bound = false;
            throw new Horde_Kolab_Server_Exception('Anonymous bind is not allowed!');
        } else {
            $this->bound = true;
        }
        return $this->bound;
    }

    /**
     * Disconnect from LDAP.
     *
     * @return NULL
     */
    public function unbind()
    {
        $this->bound = false;
    }

    /**
     * Parse LDAP filter.
     * Partially derived from Net_LDAP_Filter.
     *
     * @param string $filter The filter string.
     *
     * @return array An array of the parsed filter.
     *
     * @throws Horde_Kolab_Server_Exception If parsing the filter expression
     *                                      fails.
     */
    public function parse($filter)
    {
        $result = array();
        if (preg_match('/^\((.+?)\)$/', $filter, $matches)) {
            if (in_array(substr($matches[1], 0, 1), array('!', '|', '&'))) {
                $result['op']  = substr($matches[1], 0, 1);
                $result['sub'] = $this->parseSub(substr($matches[1], 1));
                return $result;
            } else {
                if (stristr($matches[1], ')(')) {
                    throw new Horde_Kolab_Server_Exception('Filter parsing error: invalid filter syntax - multiple leaf components detected!');
                } else {
                    $filter_parts = preg_split('/(?<!\\\\)(=|=~|>|<|>=|<=)/',
                                               $matches[1], 2,
                                               PREG_SPLIT_DELIM_CAPTURE);
                    if (count($filter_parts) != 3) {
                        throw new Horde_Kolab_Server_Exception('Filter parsing error: invalid filter syntax - unknown matching rule used');
                    } else {
                        $result['att'] = $filter_parts[0];
                        $result['log'] = $filter_parts[1];
                        $val = Net_LDAP2_Util::unescape_filter_value($filter_parts[2]);
                        $result['val'] = $val[0];
                        return $result;
                    }
                }
            }
        } else {
            throw new Horde_Kolab_Server_Exception(sprintf("Filter parsing error: %s - filter components must be enclosed in round brackets",
                                                           $filter));
        }
    }

    /**
     * Parse a LDAP subfilter.
     *
     * @param string $filter The subfilter string.
     *
     * @return array An array of the parsed subfilter.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function parseSub($filter)
    {
        $result  = array();
        $level   = 0;
        $collect = '';
        while (preg_match('/^(\(.+?\))(.*)/', $filter, $matches)) {
            if (in_array(substr($matches[1], 0, 2), array('(!', '(|', '(&'))) {
                $level++;
            }
            if ($level) {
                $collect .= $matches[1];
                if (substr($matches[2], 0, 1) == ')') {
                    $collect   .= ')';
                    $matches[2] = substr($matches[2], 1);
                    $level--;
                    if (!$level) {
                        $result[] = $this->parse($collect);
                    }
                }
            } else {
                $result[] = $this->parse($matches[1]);
            }
            $filter = $matches[2];
        }
        return $result;
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
        if (!$this->bound) {
            $result = $this->bind();
        }

        $filter = $this->parse($filter);
        if (isset($params['attributes'])) {
            $attributes = $params['attributes'];
            if (!is_array($attributes)) {
                $attributes = array($attributes);
            }
            $this->mapKeys($attributes);
        } else {
            $attributes = array();
        }
        $result = $this->doSearch($filter, $attributes);
        if (empty($result)) {
            return array();
        }
        if ($base) {
            $subtree = array();
            foreach ($result as $entry) {
                if (strpos($entry['dn'], $base)) {
                    $subtree[] = $entry;
                }
            }
            $result = $subtree;
        }

        $this->unmapAttributes($result);

        return $this->getEntries($result);
    }

    /**
     * Perform the search.
     *
     * @param array $filter     Filter criteria-
     * @param array $attributes Restrict the search result to
     *                          these attributes.
     *
     * @return array A LDAP serach result.
     *
     * @throws Horde_Kolab_Server_Exception If the search operation is not
     *                                      available.
     */
    protected function doSearch($filter, $attributes = null)
    {
        if (isset($filter['log'])) {
            $result = array();
            foreach ($this->data as $element) {
                if (isset($element['data'][$filter['att']])) {
                    switch ($filter['log']) {
                    case '=':
                        $value = $element['data'][$filter['att']];
                        if (!empty($value) && is_array($value)) {
                            $keys = array_keys($value);
                            $first = $value[$keys[0]];
                        } else {
                            $first = $value;
                        }
                        if ((($filter['val'] == '*')
                             && !empty($value))
                            || $value == $filter['val']
                            || (substr($filter['val'], 0, 1) == '*'
                                && substr($filter['val'], strlen($filter['val']) - 1) == '*'
                                && strpos($first, substr($filter['val'], 1, strlen($filter['val']) - 2)) !== false)
                            || (is_array($value)
                                && in_array($filter['val'], $value))) {
                            if (empty($attributes)) {
                                $result[] = $element;
                            } else {
                                $selection = $element;
                                foreach ($element['data'] as $attr => $value) {
                                    if (!in_array($attr, $attributes)) {
                                        unset($selection['data'][$attr]);
                                    }
                                }
                                $result[] = $selection;
                            }
                        }
                        break;
                    default:
                        throw new Horde_Kolab_Server_Exception("Not implemented!");
                    }
                }
            }
            return $result;
        } else {
            $subresult   = array();
            $filtercount = count($filter['sub']);
            foreach ($filter['sub'] as $subfilter) {
                $subresult = array_merge($subresult,
                                         $this->doSearch($subfilter,
                                                         $attributes));
            }
            $result = array();
            $dns    = array();
            foreach ($subresult as $element) {
                $dns[] = $element['dn'];

                $result[$element['dn']] = $element;
            }
            switch ($filter['op']) {
            case '&':
                $count     = array_count_values($dns);
                $selection = array();
                foreach ($count as $dn => $value) {
                    if ($value == $filtercount) {
                        $selection[] = $result[$dn];
                    }
                }
                return $selection;
            case '|':
                return array_values($result);
            case '!':
                $dns = array();
                foreach ($result as $entry) {
                    if (!in_array($entry['dn'], $dns) ) {
                        $dns[] = $entry['dn'];
                    }
                }
                $all_dns = array_keys($this->data);
                $diff    = array_diff($all_dns, $dns);

                $result = array();
                foreach ($diff as $dn) {
                    if (empty($attributes)) {
                        $result[] = $this->data[$dn];
                    } else {
                        $selection = $this->data[$dn];
                        foreach ($this->data[$dn]['data']
                                 as $attr => $value) {
                            if (!in_array($attr, $attributes)) {
                                unset($selection['data'][$attr]);
                            }
                        }
                        $result[] = $selection;
                    }
                }
                return $result;
            default:
                throw new Horde_Kolab_Server_Exception("Not implemented!");
            }
        }
    }

    /**
     * Read object data.
     *
     * @param string $dn    The object to retrieve.
     * @param string $attrs Restrict to these attributes
     *
     * @return array An array of attributes.
     *
     * @throws Horde_Kolab_Server_Exception If the object does not exist.
     */
    public function read($uid, array $attrs = array())
    {
        if (!$this->bound) {
            $result = $this->bind();
        }

        if (!isset($this->data[$dn])) {
            throw new Horde_Kolab_Server_MissingObjectException(sprintf("No such object: %s",
                                                                        $dn));
        }
        if (empty($attrs)) {
            $data = $this->data[$dn]['data'];
            $this->unmapAttributes($data);
            return $data;
        } else {
            $this->mapKeys($attrs);

            $result = array();
            $data   = $this->data[$dn]['data'];

            foreach ($attrs as $attr) {
                if (isset($data[$attr])) {
                    $result[$attr] = $data[$attr];
                }
            }

            $this->unmapAttributes($result);

            return $result;
        }
    }

    /**
     * Save an object.
     *
     * @param string  $uid     The UID of the object to be added.
     * @param array   $data    The attributes of the object to be added/replaced.
     * @param boolean $exists  Does the object already exist on the server?
     *
     * @return NULL
     */
    public function save($uid, array $data, $exists = false)
    {
        if (!$this->bound) {
            $result = $this->bind();
        }

        if ($exists === false) {

            $ldap_data = $this->_toStorage($data['add']);

            $this->data[$uid] = array(
                'dn' => $uid,
                'data' => array_merge($ldap_data,
                                      array('dn' => $uid)),
            );
        } else {

            if (isset($data['delete'])) {
                foreach ($data['delete'] as $k => $v) {
                    if (is_int($k)) {
                        $w = $this->mapField($v);
                        if (isset($this->data[$uid]['data'][$w])) {
                            /** Delete a complete attribute */
                            unset($this->data[$uid]['data'][$w]);
                        }
                    } else {
                        $l = $this->mapField($k);
                        if (isset($this->data[$uid]['data'][$l])) {
                            if (!is_array($v)) {
                                $v = array($v);
                            }
                            foreach ($v as $w) {
                                $key = array_search($w, $this->data[$uid]['data'][$l]);
                                if ($key !== false) {
                                    /** Delete a single value */
                                    unset($this->data[$uid]['data'][$l][$key]);
                                }
                            }
                        }
                    }
                }
            }

            if (isset($data['replace'])) {
                $ldap_data = $this->_toStorage($data['replace']);

                $this->data[$uid] = array(
                    'dn' => $uid,
                    'data' => array_merge($this->data[$uid]['data'],
                                          $ldap_data,
                                          array('dn' => $uid)),
                );
            }

            if (isset($data['add'])) {
                $ldap_data = $this->_toStorage($data['add']);

                foreach ($ldap_data as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $w) {
                            $this->data[$uid]['data'][$k][] = $w;
                        }
                    } else {
                        $this->data[$uid]['data'][$k][] = $v;
                    }
                    $this->data[$uid]['data'][$k] = array_values($this->data[$uid]['data'][$k]);
                }
            }
        }

        $this->store();

        if (isset($this->logger)) {
            $this->logger->debug(sprintf('The object \"%s\" has been successfully saved!',
                                         $uid));
        }
    }

    /**
     * Rewrite a data array to our internal storage format.
     *
     * @param array   $data    The attributes of the object to be added/replaced.
     *
     * @return array  The transformed data set.
     */
    private function _toStorage($data)
    {
        $this->mapAttributes($data);

        $ldap_data = array();
        foreach ($data as $key => $val) {
            if (!is_array($val)) {
                $val = array($val);
            }
            $ldap_data[$key] = $val;
        }
        return $ldap_data;
    }

    /**
     * Delete an object.
     *
     * @param string $uid The UID of the object to be deleted.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function delete($uid)
    {
        if (isset($this->data[$uid])) {
            unset($this->data[$uid]);
        } else {
            throw new Horde_Kolab_Server_MissingObjectException(sprintf("No such object: %s",
                                                                        $uid));
        }
        $this->store();
        if (isset($this->logger)) {
            $this->logger->debug(sprintf('The object \"%s\" has been successfully deleted!',
                                         $uid));
        }
    }

    /**
     * Rename an object.
     *
     * @param string $uid The UID of the object to be renamed.
     * @param string $new The new UID of the object.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function rename($uid, $new)
    {
        if (isset($this->data[$uid])) {
            $this->data[$new] = $this->data[$uid];
            unset($this->data[$uid]);
        }
        $this->store();
        if (isset($this->logger)) {
            $this->logger->debug(sprintf('The object \"%s\" has been successfully renamed to \"%s\"!',
                                         $uid, $new));
        }
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
    public function getObjectclassSchema($objectclass)
    {
        return array();
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
    public function getAttributeSchema($attribute)
    {
        return array();
    }

    /**
     * Return the current entry of a result.
     *
     * @return mixe  The current entry of the result or false.
     */
    protected function fetchEntry()
    {
        if (is_array($this->_current_result)
            && $this->_current_index < count($this->_current_result)) {

            $data = array_keys($this->_current_result[$this->_current_index]['data']);

            $data['dn'] = array($this->_current_result[$this->_current_index]['dn']);

            foreach ($this->_current_result[$this->_current_index]['data']
                     as $attr => $value) {
                if (!is_array($value)) {
                    $value = array($value);
                }
                $data[$attr] = $value;
            }
            $this->_current_index++;
            return $data;
        }
        return false;
    }

    /**
     * Return the first entry of a result.
     *
     * @param array $result The LDAP search result.
     *
     * @return mixed The first entry of the result or false.
     */
    protected function firstEntry($result)
    {
        $this->_current_result = $result;
        $this->_current_index  = 0;
        return $this->fetchEntry();
    }

    /**
     * Return the next entry of a result.
     *
     * @param resource $entry The current LDAP entry.
     *
     * @return resource The next entry of the result.
     */
    protected function nextEntry($entry)
    {
        return $this->fetchEntry();
    }

    /**
     * Return the entries of a result.
     *
     * @param array $result The LDAP search result.
     *
     * @return mixed The entries of the result or false.
     */
    protected function getEntries($result)
    {
        if (is_array($result)) {
            $data = array();
            foreach ($result as $entry) {
                $t       = $entry['data'];
                $t['dn'] = $entry['dn'];
                $data[$entry['dn']]  = $t;
            }
            return $data;
        }
        return false;
    }

    /**
     * Sort the entries of a result.
     *
     * @param resource &$result   The LDAP search result.
     * @param string   $attribute The attribute used for sorting.
     *
     * @return boolean  True if sorting succeeded.
     */
    public function sort(&$result, $attribute)
    {
        if (empty($result)) {
            return $result;
        }

        $this->_sort_by = $attribute;
        usort($result, array($this, 'resultSort'));
        return false;
    }

    /**
     * Sort two entries.
     *
     * @param array $a First entry.
     * @param array $b Second entry.
     *
     * @return int  Comparison result.
     */
    protected function resultSort($a, $b)
    {
        $x = isset($a['data'][$this->_sort_by][0])?$a['data'][$this->_sort_by][0]:'';
        $y = isset($b['data'][$this->_sort_by][0])?$b['data'][$this->_sort_by][0]:'';
        return strcasecmp($x, $y);
    }


    /**
     * Return the current LDAP error number.
     *
     * @return int  The current LDAP error number.
     */
    protected function errno()
    {
        return $this->_errno;
    }

    /**
     * Return the current LDAP error description.
     *
     * @return string  The current LDAP error description.
     */
    protected function error()
    {
        return $this->_error;
    }

    /**
     * Identify the DN of the first result entry.
     *
     * @todo Check if this could be reintegrated with the code in the LDAP handler
     *       again.
     *
     * @param array $result   The LDAP search result.
     * @param int   $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return boolean|string|array The DN(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception If the number of results did not
     *                                      meet the expectations.
     */
    protected function dnFromResult($result,
                                    $restrict = Horde_Kolab_Server::RESULT_SINGLE)
    {
        if (empty($result)) {
            return false;
        }
        $dns = array();
        foreach ($result as $entry) {
            $dns[] = $entry['dn'];
        }

        switch ($restrict) {
        case self::RESULT_STRICT:
            if (count($dns) > 1) {
                throw new Horde_Kolab_Server_Exception(sprintf("Found %s results when expecting only one!",
                                                               $count));
            }
        case self::RESULT_SINGLE:
            return $dns[0];
        case self::RESULT_MANY:
            return $dns;
        }
    }

}
