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
class Horde_Kolab_Server_Connection_Mock
implements Horde_Kolab_Server_Connection
{

    /**
     * The current database data.
     *
     * @var array
     */
    protected $data = array();

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
    public function __construct(array $params)
    {
        if (isset($params['data'])) {
            $this->data = $params['data'];
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
    }

    /**
     * Get the server read connection.
     *
     * @return mixed The connection for reading data.
     */
    public function getRead()
    {
        return $this;
    }

    /**
     * Get the server write connection.
     *
     * @return mixed The connection for writing data.
     */
    public function getWrite()
    {
        return $this;
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
    public function bind($dn = '', $pw = '')
    {
        if ($this->bound && empty($dn) && empty($pw)) {
            return true;
        }

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

        if ($this->bound) {
            $this->load();
        }

        return $this->bound;
    }

    /**
    * Get a specific entry based on the DN
    *
    * @param string $dn   DN of the entry that should be fetched
    * @param array  $attr Array of Attributes to select. If ommitted, all attributes are fetched.
    *
    * @return Net_LDAP2_Entry|Net_LDAP2_Error    Reference to a Net_LDAP2_Entry object or Net_LDAP2_Error object
    * @todo Maybe check against the shema should be done to be sure the attribute type exists
    */
    public function getEntry($dn, $attr = array())
    {
        if (!is_array($attr)) {
            $attr = array($attr);
        }
        $result = $this->search($dn, '(objectClass=*)',
                                array('scope' => 'base', 'attributes' => $attr));
        if ($result->count() == 0) {
            throw new Horde_Kolab_Server_Exception('Could not fetch entry '.$dn.': no entry found');
        }
        $entry = $result->shiftEntry();
        if (false == $entry) {
            throw new Horde_Kolab_Server_Exception('Could not fetch entry (error retrieving entry from search result)');
        }
        return $entry;
    }

    /**
     * Search for object data.
     *
     * @param string $base   The search base
     * @param string $filter The LDAP search filter.
     * @param string $params Additional search parameters.
     *
     * @return array The result array.
     *
     * @throws Horde_Kolab_Server_Exception If the search operation encountered
     *                                      a problem.
     */
    public function search($base = null, $filter = null, $params = array())
    {
        $this->bind();

        $filter = $this->parse($filter);
        if (isset($params['attributes'])) {
            $attributes = $params['attributes'];
            if (!is_array($attributes)) {
                $attributes = array($attributes);
            }
        } else {
            $attributes = array();
        }

        $result = $this->doSearch($filter, $attributes);

        if (empty($result)) {
            $search = new Horde_Kolab_Server_Connection_Mock_Search(array());
            return $search;
        }

        if (!empty($base)) {
            $subtree = array();
            foreach ($result as $entry) {
                if (strpos($entry['dn'], $base)) {
                    $subtree[] = $entry;
                }
            }
            $result = $subtree;
        }

        $search = new Horde_Kolab_Server_Connection_Mock_Search($this->getEntries($result));
        return $search;
    }

    /**
     * Return the entries of a result.
     *
     * @param array $result The LDAP search result.
     *
     * @return mixed The entries of the result or false.
     */
    public function getEntries($result)
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
    public function doSearch($filter, $attributes = null)
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
    * Add a new entryobject to a directory.
    *
    * @param Net_LDAP2_Entry $entry Net_LDAP2_Entry
    *
    * @return Net_LDAP2_Error|true Net_LDAP2_Error object or true
    */
    public function add($entry)
    {
        $this->bind();

        $ldap_data = $this->toStorage($entry->getValues());

        $guid = $entry->getDn();

        $this->data[$guid] = array(
            'dn' => $guid,
            'data' => array_merge($ldap_data,
                                  array('dn' => $guid)),
        );

        $this->store();
    }

    /**
    * Modify an ldapentry directly on the server
    *
    * @param string|Net_LDAP2_Entry &$entry DN-string or Net_LDAP2_Entry
    * @param array                 $parms  Array of changes
    *
    * @access public
    * @return Net_LDAP2_Error|true Net_LDAP2_Error object or true
    */
    public function modify($entry, $data = array())
    {
        $this->bind();

        $guid = $entry->getDn();

        if (isset($data['delete'])) {
            foreach ($data['delete'] as $k => $v) {
                if (is_int($k)) {
                    if (isset($this->data[$guid]['data'][$w])) {
                        /** Delete a complete attribute */
                        unset($this->data[$guid]['data'][$w]);
                    }
                } else {
                    if (isset($this->data[$guid]['data'][$l])) {
                        if (!is_array($v)) {
                            $v = array($v);
                        }
                        foreach ($v as $w) {
                            $key = array_search($w, $this->data[$guid]['data'][$l]);
                            if ($key !== false) {
                                /** Delete a single value */
                                unset($this->data[$guid]['data'][$l][$key]);
                            }
                        }
                    }
                }
            }
        }

        if (isset($data['replace'])) {
            $ldap_data = $this->toStorage($data['replace']);

            $this->data[$guid] = array(
                'dn' => $guid,
                'data' => array_merge($this->data[$guid]['data'],
                                      $ldap_data,
                                      array('dn' => $guid)),
            );
        }

        if (isset($data['add'])) {
            $ldap_data = $this->toStorage($data['add']);

            foreach ($ldap_data as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $w) {
                        $this->data[$guid]['data'][$k][] = $w;
                    }
                } else {
                    $this->data[$guid]['data'][$k][] = $v;
                }
                $this->data[$guid]['data'][$k] = array_values($this->data[$guid]['data'][$k]);
            }
        }

        $this->store();
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
    public function move($uid, $new)
    {
        if (isset($this->data[$uid])) {
            $this->data[$new] = $this->data[$uid];
            unset($this->data[$uid]);
        }

        $this->store();
    }

    public function schema()
    {
        //@todo: implement
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
     * Disconnect from LDAP.
     *
     * @return NULL
     */
    public function unbind()
    {
        $this->bound = false;
    }

    /**
     * Load the current state of the database.
     *
     * @return NULL
     */
    protected function load()
    {
        /**
         * @todo: remove as it does not make much sense. The file based handler
         * can do the same thing as a decorator.
         */
        if (isset($GLOBALS['KOLAB_SERVER_TEST_DATA'])) {
            $this->data = $GLOBALS['KOLAB_SERVER_TEST_DATA'];
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
     * Rewrite a data array to our internal storage format.
     *
     * @param array   $data    The attributes of the object to be added/replaced.
     *
     * @return array  The transformed data set.
     */
    protected function toStorage($data)
    {
        $ldap_data = array();
        foreach ($data as $key => $val) {
            if (!is_array($val)) {
                $val = array($val);
            }
            $ldap_data[$key] = $val;
        }
        return $ldap_data;
    }
}
