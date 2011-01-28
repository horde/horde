<?php
/**
 * Result set of an LDAP search
 *
 * @category  Horde
 * @package   Ldap
 * @author    Tarjej Huse <tarjei@bergfald.no>
 * @author    Benedikt Hallinger <beni@php.net>
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2009 Jan Wagner, Benedikt Hallinger
 * @copyright 2010-2011 The Horde Project
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL
 */
class Horde_Ldap_Search implements Iterator
{
    /**
     * Search result identifier.
     *
     * @var resource
     */
    protected $_search;

    /**
     * LDAP resource link.
     *
     * @var resource
     */
    protected $_link;

    /**
     * Horde_Ldap object.
     *
     * A reference of the Horde_Ldap object for passing to Horde_Ldap_Entry.
     *
     * @var Horde_Ldap
     */
    protected $_ldap;

    /**
     * Result entry identifier.
     *
     * @var resource
     */
    protected $_entry;

    /**
     * The errorcode from the search.
     *
     * Some errorcodes might be of interest that should not be considered
     * errors, for example:
     * - 4: LDAP_SIZELIMIT_EXCEEDED - indicates a huge search. Incomplete
     *      results are returned. If you just want to check if there is
     *      anything returned by the search at all, this could be catched.
     * - 32: no such object - search here returns a count of 0.
     *
     * @var integer
     */
    protected $_errorCode = 0;

    /**
     * Cache for all entries already fetched from iterator interface.
     *
     * @var array
     */
    protected $_iteratorCache = array();

    /**
     * Attributes we searched for.
     *
     * This variable gets set from the constructor and can be retrieved through
     * {@link searchedAttributes()}.
     *
     * @var array
     */
    protected $_searchedAttrs = array();

    /**
     * Cache variable for storing entries fetched internally.
     *
     * This currently is only used by {@link pop_entry()}.
     *
     * @var array
     */
    protected $_entry_cache = false;

    /**
     * Constructor.
     *
     * @param resource            $search     Search result identifier.
     * @param Horde_Ldap|resource $ldap       Horde_Ldap object or a LDAP link
     *                                        resource
     * @param array               $attributes The searched attribute names,
     *                                        see {@link $_searchedAttrs}.
     */
    public function __construct($search, $ldap, $attributes = array())
    {
        $this->setSearch($search);

        if ($ldap instanceof Horde_Ldap) {
            $this->_ldap = $ldap;
            $this->setLink($this->_ldap->getLink());
        } else {
            $this->setLink($ldap);
        }

        $this->_errorCode = @ldap_errno($this->_link);

        if (is_array($attributes) && !empty($attributes)) {
            $this->_searchedAttrs = $attributes;
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        @ldap_free_result($this->_search);
    }

    /**
     * Returns all entries from the search result.
     *
     * @return array  All entries.
     * @throws Horde_Ldap_Exception
     */
    public function entries()
    {
        $entries = array();
        while ($entry = $this->shiftEntry()) {
            $entries[] = $entry;
        }
        return $entries;
    }

    /**
     * Get the next entry from the search result.
     *
     * This will return a valid Horde_Ldap_Entry object or false, so you can
     * use this method to easily iterate over the entries inside a while loop.
     *
     * @return Horde_Ldap_Entry|false  Reference to Horde_Ldap_Entry object or
     *                                 false if no more entries exist.
     * @throws Horde_Ldap_Exception
     */
    public function shiftEntry()
    {
        if (!$this->count()) {
            return false;
        }

        if (is_null($this->_entry)) {
            $this->_entry = @ldap_first_entry($this->_link, $this->_search);
            $entry = Horde_Ldap_Entry::createConnected($this->_ldap, $this->_entry);
        } else {
            if (!$this->_entry = @ldap_next_entry($this->_link, $this->_entry)) {
                return false;
            }
            $entry = Horde_Ldap_Entry::createConnected($this->_ldap, $this->_entry);
        }

        return $entry;
    }

    /**
     * Retrieve the next entry in the search result, but starting from last
     * entry.
     *
     * This is the opposite to {@link shiftEntry()} and is also very useful to
     * be used inside a while loop.
     *
     * @return Horde_Ldap_Entry|false
     * @throws Horde_Ldap_Exception
     */
    public function popEntry()
    {
        if (false === $this->_entry_cache) {
            // Fetch entries into cache if not done so far.
            $this->_entry_cache = $this->entries();
        }

        return count($this->_entry_cache) ? array_pop($this->_entry_cache) : false;
    }

    /**
     * Return entries sorted as array.
     *
     * This returns a array with sorted entries and the values. Sorting is done
     * with PHPs {@link array_multisort()}.
     *
     * This method relies on {@link asArray()} to fetch the raw data of the
     * entries.
     *
     * Please note that attribute names are case sensitive!
     *
     * Usage example:
     * <code>
     *   // To sort entries first by location, then by surname, but descending:
     *   $entries = $search->sortedAsArray(array('locality', 'sn'), SORT_DESC);
     * </code>
     *
     * @todo what about server side sorting as specified in
     *       http://www.ietf.org/rfc/rfc2891.txt?
     * @todo Nuke evil eval().
     *
     * @param array   $attrs Attribute names as sort criteria.
     * @param integer $order Ordering direction, either constant SORT_ASC or
     *                       SORT_DESC
     *
     * @return array Sorted entries.
     * @throws Horde_Ldap_Exception
     */
    public function sortedAsArray(array $attrs = array('cn'),
                                     $order = SORT_ASC)
    {
        /* Old Code, suitable and fast for single valued sorting. This code
         * should be used if we know that single valued sorting is desired, but
         * we need some method to get that knowledge... */
        /*
        $attrs = array_reverse($attrs);
        foreach ($attrs as $attribute) {
            if (!ldap_sort($this->_link, $this->_search, $attribute)) {
                throw new Horde_Ldap_Exception('Sorting failed for attribute ' . $attribute);
            }
        }

        $results = ldap_get_entries($this->_link, $this->_search);

        unset($results['count']);
        if ($order) {
            return array_reverse($results);
        }
        return $results;
        */

        /* New code: complete "client side" sorting */
        // First some parameterchecks.
        if ($order != SORT_ASC && $order != SORT_DESC) {
            throw new Horde_Ldap_Exception('Sorting failed: sorting direction not understood! (neither constant SORT_ASC nor SORT_DESC)');
        }

        // Fetch the entries data.
        $entries = $this->asArray();

        // Now sort each entries attribute values.
        // This is neccessary because later we can only sort by one value, so
        // we need the highest or lowest attribute now, depending on the
        // selected ordering for that specific attribute.
        foreach ($entries as $dn => $entry) {
            foreach ($entry as $attr_name => $attr_values) {
                sort($entries[$dn][$attr_name]);
                if ($order == SORT_DESC) {
                    array_reverse($entries[$dn][$attr_name]);
                }
            }
        }

        // Reformat entries array for later use with
        // array_multisort(). $to_sort will be a numeric array similar to
        // ldap_get_entries().
        $to_sort = array();
        foreach ($entries as $dn => $entry_attr) {
            $row = array('dn' => $dn);
            foreach ($entry_attr as $attr_name => $attr_values) {
                $row[$attr_name] = $attr_values;
            }
            $to_sort[] = $row;
        }

        // Build columns for array_multisort(). Each requested attribute is one
        // row.
        $columns = array();
        foreach ($attrs as $attr_name) {
            foreach ($to_sort as $key => $row) {
                $columns[$attr_name][$key] =& $to_sort[$key][$attr_name][0];
            }
        }

        // Sort the colums with array_multisort() if there is something to sort
        // and if we have requested sort columns.
        if (!empty($to_sort) && !empty($columns)) {
            $sort_params = '';
            foreach ($attrs as $attr_name) {
                $sort_params .= '$columns[\'' . $attr_name . '\'], ' . $order . ', ';
            }
            eval("array_multisort($sort_params \$to_sort);");
        }

        return $to_sort;
    }

    /**
     * Returns entries sorted as objects.
     *
     * This returns a array with sorted Horde_Ldap_Entry objects. The sorting
     * is actually done with {@link sortedAsArray()}.
     *
     * Please note that attribute names are case sensitive!
     *
     * Also note that it is (depending on server capabilities) possible to let
     * the server sort your results. This happens through search controls and
     * is described in detail at {@link http://www.ietf.org/rfc/rfc2891.txt}
     *
     * Usage example:
     * <code>
     *   // To sort entries first by location, then by surname, but descending:
     *   $entries = $search->sorted(array('locality', 'sn'), SORT_DESC);
     * </code>
     *
     * @todo Entry object construction could be faster. Maybe we could use one
     *       of the factories instead of fetching the entry again.
     *
     * @param array   $attrs Attribute names as sort criteria.
     * @param integer $order Ordering direction, either constant SORT_ASC or
     *                       SORT_DESC
     *
     * @return array Sorted entries.
     * @throws Horde_Ldap_Exception
     */
    public function sorted($attrs = array('cn'), $order = SORT_ASC)
    {
        $return = array();
        $sorted = $this->sortedAsArray($attrs, $order);
        foreach ($sorted as $row) {
            $entry = $this->_ldap->getEntry($row['dn'], $this->searchedAttributes());
            array_push($return, $entry);
        }
        return $return;
    }

    /**
     * Returns entries as array.
     *
     * The first array level contains all found entries where the keys are the
     * DNs of the entries. The second level arrays contian the entries
     * attributes such that the keys is the lowercased name of the attribute
     * and the values are stored in another indexed array. Note that the
     * attribute values are stored in an array even if there is no or just one
     * value.
     *
     * The array has the following structure:
     * <code>
     * array(
     *     'cn=foo,dc=example,dc=com' => array(
     *         'sn'       => array('foo'),
     *         'multival' => array('val1', 'val2', 'valN')),
     *     'cn=bar,dc=example,dc=com' => array(
     *         'sn'       => array('bar'),
     *         'multival' => array('val1', 'valN')))
     * </code>
     *
     * @return array Associative result array as described above.
     * @throws Horde_Ldap_Exception
     */
    public function asArray()
    {
        $return  = array();
        $entries = $this->entries();
        foreach ($entries as $entry) {
            $attrs            = array();
            $entry_attributes = $entry->attributes();
            foreach ($entry_attributes as $attr_name) {
                $attr_values = $entry->getValue($attr_name, 'all');
                if (!is_array($attr_values)) {
                    $attr_values = array($attr_values);
                }
                $attrs[$attr_name] = $attr_values;
            }
            $return[$entry->dn()] = $attrs;
        }
        return $return;
    }

    /**
     * Sets the search objects resource link
     *
     * @param resource $search Search result identifier.
     */
    public function setSearch($search)
    {
        $this->_search = $search;
    }

    /**
     * Sets the LDAP resource link.
     *
     * @param resource $link LDAP link identifier.
     */
    public function setLink($link)
    {
        $this->_link = $link;
    }

    /**
     * Returns the number of entries in the search result.
     *
     * @return integer Number of found entries.
     */
    public function count()
    {
        // This catches the situation where OL returned errno 32 = no such
        // object!
        if (!$this->_search) {
            return 0;
        }
        return @ldap_count_entries($this->_link, $this->_search);
    }

    /**
     * Returns the errorcode from the search.
     *
     * @return integer The LDAP error number.
     */
    public function getErrorCode()
    {
        return $this->_errorCode;
    }

    /**
     * Returns the attribute names this search selected.
     *
     * @see $_searchedAttrs
     *
     * @return array
     */
    protected function searchedAttributes()
    {
        return $this->_searchedAttrs;
    }

    /**
     * Returns wheter this search exceeded a sizelimit.
     *
     * @return boolean  True if the size limit was exceeded.
     */
    public function sizeLimitExceeded()
    {
        return $this->getErrorCode() == 4;
    }

    /* SPL Iterator interface methods. This interface allows to use
     * Horde_Ldap_Search objects directly inside a foreach loop. */

    /**
     * SPL Iterator interface: Returns the current element.
     *
     * The SPL Iterator interface allows you to fetch entries inside
     * a foreach() loop: <code>foreach ($search as $dn => $entry) { ...</code>
     *
     * Of course, you may call {@link current()}, {@link key()}, {@link next()},
     * {@link rewind()} and {@link valid()} yourself.
     *
     * If the search throwed an error, it returns false. False is also
     * returned, if the end is reached.
     *
     * In case no call to next() was made, we will issue one, thus returning
     * the first entry.
     *
     * @return Horde_Ldap_Entry|false
     * @throws Horde_Ldap_Exception
     */
    public function current()
    {
        if (count($this->_iteratorCache) == 0) {
            $this->next();
            reset($this->_iteratorCache);
        }
        $entry = current($this->_iteratorCache);
        return $entry instanceof Horde_Ldap_Entry ? $entry : false;
    }

    /**
     * SPL Iterator interface: Returns the identifying key (DN) of the current
     * entry.
     *
     * @see current()
     * @return string|false DN of the current entry; false in case no entry is
     *                      returned by current().
     */
    public function key()
    {
        $entry = $this->current();
        return $entry instanceof Horde_Ldap_Entry ? $entry->dn() :false;
    }

    /**
     * SPL Iterator interface: Moves forward to next entry.
     *
     * After a call to {@link next()}, {@link current()} will return the next
     * entry in the result set.
     *
     * @see current()
     * @throws Horde_Ldap_Exception
     */
    public function next()
    {
        // Fetch next entry. If we have no entries anymore, we add false (which
        // is returned by shiftEntry()) so current() will complain.
        if (count($this->_iteratorCache) - 1 <= $this->count()) {
            $this->_iteratorCache[] = $this->shiftEntry();
        }

        // Move array pointer to current element.  Even if we have added all
        // entries, this will ensure proper operation in case we rewind().
        next($this->_iteratorCache);
    }

    /**
     * SPL Iterator interface: Checks if there is a current element after calls
     * to {@link rewind()} or {@link next()}.
     *
     * Used to check if we've iterated to the end of the collection.
     *
     * @see current()
     * @return boolean False if there's nothing more to iterate over.
     */
    public function valid()
    {
        return $this->current() instanceof Horde_Ldap_Entry;
    }

    /**
     * SPL Iterator interface: Rewinds the Iterator to the first element.
     *
     * After rewinding, {@link current()} will return the first entry in the
     * result set.
     *
     * @see current()
     */
    public function rewind()
    {
        reset($this->_iteratorCache);
    }
}
