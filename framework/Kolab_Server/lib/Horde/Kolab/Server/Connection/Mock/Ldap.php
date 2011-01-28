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
class Horde_Kolab_Server_Connection_Mock_Ldap
{
    /**
     * Connection parameters.
     *
     * @var array
     */
    private $_params;

    /**
     * The current database data.
     *
     * @var array
     */
    private $_data;

    /**
     * Is the connection bound via username / password?
     *
     * @var boolean
     */
    private $_bound = false;

    /**
     * Constructor.
     *
     * @param array $params Connection parameters.
     * @param array $data   Mockup LDAP data.
     */
    public function __construct(array $params, array $data)
    {
        $this->_params = $params;
        $this->_data   = $data;
    }

    /**
     * Binds the LDAP connection with a specific user and pass.
     *
     * @param string $dn DN to bind with
     * @param string $pw Password associated to this DN.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception If the user does not exit, he has no
     *                                      password, provided an incorrect
     *                                      password or anonymous binding is not
     *                                      allowed.
     */
    public function bind($dn = '', $pw = '')
    {
        if ($dn == '' && $pw == '') {
            if (isset($this->_params['binddn'])
                && isset($this->_params['bindpw'])) {
                $dn = $this->_params['binddn'];
                $pw = $this->_params['bindpw'];
            }
        }

        if ($dn != '') {
            if (!isset($this->_data[$dn])) {
                throw new Horde_Kolab_Server_Exception('User does not exist!');
            }

            if (!isset($this->_data[$dn]['data']['userPassword'][0])) {
                throw new Horde_Kolab_Server_Exception('User has no password entry!');
            }
            if ($this->_data[$dn]['data']['userPassword'][0] != $pw) {
                throw new Horde_Kolab_Server_Exception_Bindfailed('Incorrect password!');
            }
        } else if (!empty($this->_params['no_anonymous_bind'])) {
            throw new Horde_Kolab_Server_Exception('Anonymous bind is not allowed!');
        }

        $this->_bound = true;
    }

    /**
    * Get a specific entry based on the DN
    *
    * @param string $dn   DN of the entry that should be fetched
    * @param array  $attr Array of Attributes to select. If ommitted, all attributes are fetched.
    *
    * @return Horde_Ldap_Entry|Horde_Ldap_Error    Reference to a Horde_Ldap_Entry object or Horde_Ldap_Error object
    * @todo Maybe check against the shema should be done to be sure the attribute type exists
    */
    public function getEntry($dn, $attr = array())
    {
        $this->_checkBound();

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
        $this->_checkBound();

        if (isset($params['attributes'])) {
            $attributes = $params['attributes'];
            if (!is_array($attributes)) {
                $attributes = array($attributes);
            }
        } else {
            $attributes = array();
        }

        $result = array();

        if (!empty($filter)) {
            $filter = $this->parse($filter);
            $result = $this->doSearch($filter, $attributes);
        } else {
            if (!isset($params['scope']) || $params['scope'] == 'sub') {
                foreach (array_keys($this->_data) as $dn) {
                    if (empty($attributes)) {
                        $result[] = $this->_data[$dn];
                    } else {
                        $selection = $this->_data[$dn];
                        foreach ($this->_data[$dn]['data']
                                 as $attr => $value) {
                            if (!in_array($attr, $attributes)) {
                                unset($selection['data'][$attr]);
                            }
                        }
                        $result[] = $selection;
                    }
                }
            } else if ($params['scope'] == 'base') {
                if (isset($this->_data[$base])) {
                    $result[] = $this->_data[$base];
                } else {
                    $result = array();
                }
            } else if ($params['scope'] == 'one') {
                throw new Horde_Kolab_Server_Exception('Not implemented!');
            }
        }

        if (empty($result)) {
            $search = new Horde_Kolab_Server_Connection_Mock_Search(array());
            return $search;
        }

        if (!empty($base)) {
            $subtree = array();
            foreach ($result as $entry) {
                if (preg_match('/' . $base . '$/', $entry['dn'])) {
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
                        $val = Horde_Ldap_Util::unescapeFilterValue($filter_parts[2]);
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
            foreach ($this->_data as $element) {
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
                $all_dns = array_keys($this->_data);
                $diff    = array_diff($all_dns, $dns);

                $result = array();
                foreach ($diff as $dn) {
                    if (empty($attributes)) {
                        $result[] = $this->_data[$dn];
                    } else {
                        $selection = $this->_data[$dn];
                        foreach ($this->_data[$dn]['data']
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
    * @param Horde_Ldap_Entry $entry Horde_Ldap_Entry
    *
    * @return Horde_Ldap_Error|true Horde_Ldap_Error object or true
    */
    public function add($entry)
    {
        $this->_checkBound();

        $ldap_data = $this->toStorage($entry->getValues());

        $guid = $entry->getDn();

        $this->_data[$guid] = array(
            'dn' => $guid,
            'data' => array_merge($ldap_data,
                                  array('dn' => $guid)),
        );
    }

    /**
    * Modify an ldapentry directly on the server
    *
    * @param string|Horde_Ldap_Entry &$entry DN-string or Horde_Ldap_Entry
    * @param array                 $parms  Array of changes
    *
    * @access public
    * @return Horde_Ldap_Error|true Horde_Ldap_Error object or true
    */
    public function modify($entry, $data = array())
    {
        $this->_checkBound();

        $guid = $entry->getDn();

        if (isset($data['delete'])) {
            foreach ($data['delete'] as $k => $v) {
                if (is_int($k)) {
                    if (isset($this->_data[$guid]['data'][$w])) {
                        /** Delete a complete attribute */
                        unset($this->_data[$guid]['data'][$w]);
                    }
                } else {
                    if (isset($this->_data[$guid]['data'][$l])) {
                        if (!is_array($v)) {
                            $v = array($v);
                        }
                        foreach ($v as $w) {
                            $key = array_search($w, $this->_data[$guid]['data'][$l]);
                            if ($key !== false) {
                                /** Delete a single value */
                                unset($this->_data[$guid]['data'][$l][$key]);
                            }
                        }
                    }
                }
            }
        }

        if (isset($data['replace'])) {
            $ldap_data = $this->toStorage($data['replace']);

            $this->_data[$guid] = array(
                'dn' => $guid,
                'data' => array_merge($this->_data[$guid]['data'],
                                      $ldap_data,
                                      array('dn' => $guid)),
            );
        }

        if (isset($data['add'])) {
            $ldap_data = $this->toStorage($data['add']);

            foreach ($ldap_data as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $w) {
                        $this->_data[$guid]['data'][$k][] = $w;
                    }
                } else {
                    $this->_data[$guid]['data'][$k][] = $v;
                }
                $this->_data[$guid]['data'][$k] = array_values($this->_data[$guid]['data'][$k]);
            }
        }
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
        $this->_checkBound();

        if (isset($this->_data[$uid])) {
            unset($this->_data[$uid]);
        } else {
            throw new Horde_Kolab_Server_MissingObjectException(sprintf("No such object: %s",
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
    public function move($uid, $new)
    {
        $this->_checkBound();

        if (isset($this->_data[$uid])) {
            $this->_data[$new] = $this->_data[$uid];
            unset($this->_data[$uid]);
        }
    }

    public function schema()
    {
        //@todo: implement
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

    /**
     * Check if the current connection is bound.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception If the connection is not bound.
     */
    private function _checkBound()
    {
        if (!$this->_bound) {
            throw new Horde_Kolab_Server_Exception('Unbound connection!');
        }
    }
}
