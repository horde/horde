<?php
/**
 * The IMP_Search:: class contains all code related to mailbox searching
 * in IMP.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Search implements ArrayAccess, Iterator, Serializable
{
    /* The mailbox search prefix. */
    const MBOX_PREFIX = "impsearch\0";

    /* The special search mailbox names. */
    const BASIC_SEARCH = 'impbsearch';
    const DIMP_FILTERSEARCH = 'dimpfsearch';
    const DIMP_QUICKSEARCH = 'dimpqsearch';

    /* Bitmask filters for iterator. */
    const LIST_FILTER = 1;
    const LIST_QUERY = 2;
    const LIST_VFOLDER = 4;
    const LIST_DISABLED = 8;

    /* Query creation types. */
    const CREATE_FILTER = 1;
    const CREATE_QUERY = 2;
    const CREATE_VFOLDER = 3;

    /**
     * Has the object data changed?
     *
     * @var boolean
     */
    public $changed = false;

    /**
     * Iterator filter mask.
     *
     * @var integer
     */
    protected $_filter = 0;

    /**
     * Iterator pointer.
     *
     * @var array
     */
    protected $_iteratorPtr;

    /**
     * Search queries.
     *
     * Each subarray contains:
     *   Keys: mailbox IDs.
     *   Values: IMP_Search_Query objects.
     *
     * @var array
     */
    protected $_search = array(
        'filters' => array(),
        'query' => array(),
        'vfolders' => array()
    );

    /**
     * Initialize session search data.
     */
    public function init()
    {
        $this->setFilters($this->getFilters(), false);
        $this->setVFolders($this->getVFolders(), false);
    }

    /**
     * Run a search.
     *
     * @param object $ob  An optional search query to add (via 'AND') to the
     *                    active search (Horde_Imap_Client_Search_Query).
     * @param string $id  The search query id.
     *
     * @return IMP_Indices  An indices object.
     * @throws Horde_Imap_Client_Exception
     */
    public function runSearch($ob, $id)
    {
        $id = $this->_strip($id);
        $mbox = '';
        $sorted = new IMP_Indices();

        if (!($query_list = $this[$id]->query)) {
            return $sorted;
        }

        /* How do we want to sort results? */
        $sortpref = IMP::getSort(null, true);
        if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
            $sortpref['by'] = $GLOBALS['prefs']->getValue('sortdate');
        }

        foreach ($query_list as $mbox => $query) {
            if (!empty($ob)) {
                $query->andSearch(array($ob));
            }
            $results = $this->imapSearch($mbox, $query, array('reverse' => $sortpref['dir'], 'sort' => array($sortpref['by'])));
            $sorted->add($mbox, $results['sort']);
        }

        return $sorted;
    }

    /**
     * Run a search query not stored in the current session.  Allows custom
     * queries with custom sorts to be used without affecting cached
     * mailboxes.
     *
     * @param object $query     The search query object
     *                          (Horde_Imap_Client_Search_Query).
     * @param string $mailbox   The mailbox to search.
     * @param integer $sortby   The sort criteria.
     * @param integer $sortdir  The sort directory.
     *
     * @return IMP_Indices  An indices object.
     */
    public function runQuery($query, $mailbox, $sortby = null,
                             $sortdir = null)
    {
        try {
            $results = $this->imapSearch($mailbox, $query, array('reverse' => $sortdir, 'sort' => is_null($sortby) ? null : array($sortby)));
            return new IMP_Indices($mailbox, is_null($sortby) ? $results['match'] : $results['sort']);
        } catch (Horde_Imap_Client_Exception $e) {
            return new IMP_Indices();
        }
    }

    /**
     * Performs the IMAP search query on the server. Use this function,
     * instead of directly calling Horde_Imap_Client's search() function,
     * because certain configuration parameters may need to be dynamically
     * altered.
     *
     * @param string $mailbox                        The mailbox to search.
     * @param Horde_Imap_Client_Search_Query $query  The search query object.
     * @param array $opts                            Additional options.
     *
     * @return array  Search results.
     */
    public function imapSearch($mailbox, $query, $opts = array())
    {
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create();

        /* If doing a from/to search, use display sorting if possible.
         * Although there is a fallback to a PHP-based display sort, for
         * performance reasons only do a display sort if it is supported
         * on the server. */
        if (($GLOBALS['session']->get('imp', 'protocol') == 'imap') &&
            !empty($opts['sort'])) {
            $sort_cap = $imp_imap->queryCapability('SORT');

            if (is_array($sort_cap) && in_array('DISPLAY', $sort_cap)) {
                $pos = array_search(Horde_Imap_Client::SORT_FROM, $opts['sort']);
                if ($pos !== false) {
                    $opts['sort'][$pos] = Horde_Imap_Client::SORT_DISPLAYFROM;
                }

                $pos = array_search(Horde_Imap_Client::SORT_TO, $opts['sort']);
                if ($pos !== false) {
                    $opts['sort'][$pos] = Horde_Imap_Client::SORT_DISPLAYTO;
                }
            }
        }

        /* Make sure we search in the proper charset. */
        if ($query) {
            $query = clone $query;
            $imap_charset = $imp_imap->validSearchCharset('UTF-8')
                ? 'UTF-8'
                : 'US-ASCII';
            $query->charset($imap_charset, array('Horde_String', 'convertCharset'));
        }

        return $imp_imap->search($mailbox, $query, $opts);
    }

    /**
     * Creates the IMAP search query in the IMP session.
     *
     * @param array $criteria  The search criteria array.
     * @param array $mboxes    The list of mailboxes to search.
     * @param string $label    The label to use for the search results.
     * @param integer $type    Query type.
     * @param string $id       Use as the mailbox ID.
     *
     * @return IMP_Search_Query  Returns the query object.
     * @throws InvalidArgumentException
     */
    public function createQuery($criteria, $mboxes = array(), $label = null,
                                $type = self::CREATE_QUERY, $id = null)
    {
        if (!is_null($id)) {
            $id = $this->_strip($id);
        }

        switch ($type) {
        case self::CREATE_FILTER:
            $cname = 'IMP_Search_Filter';
            break;

        case self::CREATE_QUERY:
            $cname = 'IMP_Search_Query';
            if (empty($mboxes)) {
                throw new InvalidArgumentException('Search query requires at least one mailbox.');
            }
            break;

        case self::CREATE_VFOLDER:
            $cname = 'IMP_Search_Vfolder';
            if (empty($mboxes)) {
                throw new InvalidArgumentException('Search query requires at least one mailbox.');
            }
            break;
        }

        $ob = new $cname(array_filter(array(
            'add' => $criteria,
            'id' => $id,
            'label' => $label,
            'mboxes' => $mboxes
        )));

        switch ($type) {
        case self::CREATE_FILTER:
            /* This will overwrite previous value, if it exists. */
            $this->_search['filters'][$ob->id] = $ob;
            $this->setFilters($this->_search['filters']);
            break;

        case self::CREATE_QUERY:
            $this->_search['query'][$ob->id] = $ob;
            break;

        case self::CREATE_VFOLDER:
            /* This will overwrite previous value, if it exists. */
            $this->_search['vfolders'][$ob->id] = $ob;
            $this->setVFolders($this->_search['vfolders']);
            break;
        }

        $this->changed = true;

        return $ob;
    }

    /**
     * Obtains the list of filters for the current user.
     *
     * @return array  The list of filters.  Keys are mailbox IDs, values are
     *                IMP_Search_Filter objects.
     */
    public function getFilters()
    {
        $filters = array();

        /* Build list of default filters. */
        $di = new DirectoryIterator(IMP_BASE . '/lib/Search/Filter');
        foreach ($di as $val) {
            if ($val->isFile()) {
                $cname = 'IMP_Search_Filter_' . $val->getBasename('.php');
                if (($cname != 'IMP_Search_Filter_Builtin') &&
                    class_exists($cname)) {
                    $filter = new $cname();
                    $filters[$filter->id] = $filter;
                }
            }
        }

        if ($f_list = $GLOBALS['prefs']->getValue('filter')) {
            $f_list = @unserialize($f_list);
            if (is_array($f_list)) {
                foreach ($f_list as $val) {
                    if ($val instanceof IMP_Search_Filter) {
                        $filters[$val->id] = $val;
                    }
                }
            }
        }

        return $filters;
    }

    /**
     * Saves the list of filters for the current user.
     *
     * @param array $filters  The filter list.
     * @param boolean $save   Save the filter list to the preference backend?
     */
    public function setFilters($filters, $save = true)
    {
        if ($save) {
            $GLOBALS['prefs']->setValue('filter', serialize(array_values($filters)));
        }

        $this->_search['filters'] = $filters;
        $this->changed = true;
    }

    /**
     * Is a mailbox a filter query?
     *
     * @param string $id         The mailbox ID.
     * @param boolean $editable  Is this an editable (i.e. not built-in)
     *                           filter query?
     */
    public function isFilter($id, $editable = false)
    {
        return (isset($this->_search['filters'][$this->_strip($id)]) &&
                (!$editable || $this[$id]->canEdit));
    }

    /**
     * Converts a filter to a search query and stores it in the local
     * session.
     *
     * @param string $id     The mailbox ID of the filter.
     * @param array $mboxes  The list of mailboxes to apply the filter on.
     * @param string $mid    Use as the mailbox ID.
     *
     * @return IMP_Search_Query  The created query object.
     * @throws InvalidArgumentException
     */
    public function applyFilter($id, array $mboxes, $mid = null)
    {
        if (!$this->isFilter($id)) {
            throw new InvalidArgumentException('Invalid filter ID given.');
        }

        if (!is_null($mid)) {
            $mid = $this->_strip($mid);
        }

        $q_ob = $this[$id]->toQuery($mboxes, $mid);
        $this->_search['query'][$q_ob->id] = $q_ob;
        $this->changed = true;

        return $q_ob;
    }

    /**
     * Obtains the list of virtual folders for the current user.
     *
     * @return array  The list of virtual folders.  Keys are mailbox IDs,
     *                values are IMP_Search_Vfolder objects.
     */
    public function getVFolders()
    {
        $vf = array();

        /* Build list of default virtual folders. */
        $di = new DirectoryIterator(IMP_BASE . '/lib/Search/Vfolder');
        $disabled = array('IMP_Search_Vfolder_Vtrash');

        foreach ($di as $val) {
            if ($val->isFile()) {
                $cname = 'IMP_Search_VFolder_' . $val->getBasename('.php');
                if (($cname != 'IMP_Search_VFolder_Builtin') &&
                    class_exists($cname)) {
                    $filter = new $cname(array(
                        'disabled' => in_array($cname, $disabled)
                    ));
                    $filters[$filter->id] = $filter;
                }
            }
        }

        if ($pref_vf = $GLOBALS['prefs']->getValue('vfolder')) {
            $pref_vf = @unserialize($pref_vf);
            if (is_array($pref_vf)) {
                foreach ($pref_vf as $val) {
                    if ($val instanceof IMP_Search_Vfolder) {
                        $vf[$val->id] = $val;
                    }
                }
            }
        }

        return $vf;
    }

    /**
     * Saves the list of virtual folders for the current user.
     *
     * @param array $vfolders  The virtual folder list.
     * @param boolean $save    Save the virtual folder list to the preference
     *                         backend?
     */
    public function setVFolders($vfolders, $save = true)
    {
        if ($save) {
            $GLOBALS['prefs']->setValue('vfolder', serialize(array_values($vfolders)));
        }

        $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->updateVFolders($vfolders);
        $this->_search['vfolders'] = $vfolders;
        $this->changed = true;
    }

    /**
     * Is a mailbox a virtual folder?
     *
     * @param string $id         The mailbox ID.
     * @param boolean $editable  Is this an editable (i.e. not built-in)
     *                           virtual folder?
     *
     * @return boolean  Whether the mailbox ID is a virtual folder.
     */
    public function isVFolder($id, $editable = false)
    {
        return (isset($this->_search['vfolders'][$this->_strip($id)]) &&
                (!$editable || $this[$id]->canEdit));
    }

    /**
     * Determines whether a mailbox ID is the Virtual Trash Folder.
     *
     * @param string $id  The mailbox id.
     *
     * @return boolean  True if the ID is the Virtual Trash folder.
     */
    public function isVTrash($id)
    {
        return (($this->isVFolder($id)) &&
            ($this[$id] instanceof IMP_Search_Vfolder_Vtrash));
    }

    /**
     * Determines whether a mailbox ID is the Virtual INBOX Folder.
     *
     * @param string $id  The mailbox id.
     *
     * @return boolean  True if the ID is the Virtual INBOX folder.
     */
    public function isVinbox($id)
    {
        return (($this->isVFolder($id)) &&
            ($this[$id] instanceof IMP_Search_Vfolder_Vinbox));
    }

    /**
     * Is a mailbox a search query?
     *
     * @param string $id         The mailbox ID.
     * @param boolean $editable  Is this an editable (i.e. not built-in)
     *                           search query?
     */
    public function isQuery($id, $editable = false)
    {
        return (isset($this->_search['query'][$this->_strip($id)]) &&
                (!$editable ||
                 !in_array($this[$id]->id, array(self::BASIC_SEARCH, self::DIMP_FILTERSEARCH, self::DIMP_QUICKSEARCH))));
    }

    /**
     * Returns a link to edit a given search query.
     *
     * @param string $id  The search query id.
     *
     * @return Horde_Url  The URL to the search page.
     */
    public function editUrl($id)
    {
        return Horde::url('search.php')->add(array('edit_query' => $this->createSearchId($id)));
    }

    /**
     * Is the given mailbox a search mailbox?
     *
     * @param string $id  The mailbox name.
     *
     * @return boolean  Whether the given mailbox name is a search mailbox.
     */
    public function isSearchMbox($id)
    {
        return (strpos($id, self::MBOX_PREFIX) === 0);
    }

    /**
     * Strip the identifying label from a mailbox ID.
     *
     * @param string $id  The mailbox query ID.
     *
     * @return string  The virtual folder ID, with any IMP specific
     *                 identifying information stripped off.
     */
    protected function _strip($id)
    {
        return $this->isSearchMbox($id)
            ? substr($id, strlen(self::MBOX_PREFIX))
            : $id;
    }

    /**
     * Create the canonical search ID for a given search query.
     *
     * @param string $id  The mailbox query ID.
     *
     * @return string  The canonical search query ID.
     */
    public function createSearchId($id)
    {
        return self::MBOX_PREFIX . $this->_strip($id);
    }

    /* ArrayAccess methods. */

    public function offsetExists($offset)
    {
        $id = $this->_strip($offset);

        foreach (array_keys($this->_search) as $key) {
            if (isset($this->_search[$key][$id])) {
                return true;
            }
        }

        return false;
    }

    public function offsetGet($offset)
    {
        $id = $this->_strip($offset);

        foreach (array_keys($this->_search) as $key) {
            if (isset($this->_search[$key][$id])) {
                return $this->_search[$key][$id];
            }
        }

        return null;
    }

    /**
     * Alter the current IMAP search query.
     *
     * @param string $id               The search query id.
     * @param IMP_Search_Query $value  The query object.
     *
     * @throws InvalidArgumentException
     */
    public function offsetSet($offset, $value)
    {
        if (!($value instanceof IMP_Search_Query)) {
            throw new InvalidArgumentException('$value must be a query object.');
        }

        $id = $this->_strip($offset);

        foreach (array_keys($this->_search) as $key) {
            if (isset($this->_search[$key][$id])) {
                $this->_search[$key][$id] = $value;
                return;
            }
        }

        throw new InvalidArgumentException('Creating search queries by array index is not supported. Use createQuery() instead.');
    }

    /**
     * Deletes an IMAP search query.
     *
     * @param string $id  The search query id.
     */
    public function offsetUnset($offset)
    {
        $id = $this->_strip($offset);

        foreach (array_keys($this->_search) as $val) {
            if (isset($this->_search[$val][$id])) {
                unset($this->_search[$val][$id]);
                $this->changed = true;

                if ($val == 'vfolders') {
                    $this->setVFolders($this->_search['vfolders']);
                }
                break;
            }
        }
    }

    /* Iterator methods. */

    public function current()
    {
        return (($key = key($this->_search)) !== null)
            ? current($this->_search[$key])
            : null;
    }

    public function key()
    {
        return (($key = key($this->_search)) !== null)
            ? key($this->_search[$key])
            : null;
    }

    public function next()
    {
        $curr = null;

        while (($skey = key($this->_search)) !== null) {
            /* When switching between search types, need to catch the first
             * element of the new array. */
            $curr = is_null($curr)
                ? next($this->_search[$skey])
                : current($this->_search[$skey]);

            while ($curr !== false) {
                if ($this->_currValid()) {
                    return;
                }
                $curr = next($this->_search[$skey]);
            }

            next($this->_search);
        }
    }

    public function rewind()
    {
        foreach (array_keys($this->_search) as $key) {
            reset($this->_search[$key]);
        }
        reset($this->_search);

        if ($this->valid() && !$this->_currValid()) {
            $this->next();
        }
    }

    public function valid()
    {
        return (key($this->_search) !== null);
    }

    /* Helper functions for Iterator methods. */

    /**
     * Set the current iterator filter and reset the internal pointer.
     *
     * @param integer $mask  A mask with the following possible elements:
     * <pre>
     * IMP_Search::LIST_FILTER
     * IMP_Search::LIST_QUERY
     * IMP_Search::LIST_VFOLDER
     * </pre>
     */
    public function setIteratorFilter($mask = 0)
    {
        $this->_filter = $mask;
        reset($this);
    }

    /**
     * Returns true if the current object is valid given the current filter.
     *
     * @return boolean  True if the object should be displayed.
     */
    protected function _currValid()
    {
        if (!($ob = $this->current())) {
            return false;
        }

        if ($ob->enabled ||
            ($this->_filter & self::LIST_DISABLED)) {
            if (!$this->_filter) {
                return true;
            }

            if (($this->_filter & self::LIST_FILTER) &&
                $this->isFilter($ob)) {
                return true;
            }

            if (($this->_filter & self::LIST_QUERY) &&
                $this->isQuery($ob)) {
                return true;
            }

            if (($this->_filter & self::LIST_VFOLDER) &&
                $this->isVfolder($ob)) {
                return true;
            }
        }

        return false;
    }

    /* Serializable methods. */

    /**
     * Serialize.
     *
     * @return string  Serialized representation of this object.
     */
    public function serialize()
    {
        return serialize($this->_search);
    }

    /**
     * Unserialize.
     *
     * @param string $data  Serialized data.
     *
     * @throws Exception
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data)) {
            throw new Exception('Cache version change');
        }

        $this->_search = $data;
    }

}
