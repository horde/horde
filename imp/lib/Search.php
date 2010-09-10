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
class IMP_Search implements Serializable
{
    /* The mailbox search prefix. */
    const MBOX_PREFIX = "impsearch\0";

    /* The special search mailbox names. */
    const BASIC_SEARCH = 'impbsearch';
    const DIMP_FILTERSEARCH = 'dimpfsearch';
    const DIMP_QUICKSEARCH = 'dimpqsearch';

    /* Bitmask constants for listQueries(). */
    const LIST_SEARCH = 1;
    const LIST_VFOLDER = 2;
    const NO_BASIC_SEARCH = 4;

    /**
     * Has the object data changed?
     *
     * @var boolean
     */
    public $changed = false;

    /**
     * Save Virtual Folder information when adding entries?
     *
     * @var boolean
     */
    protected $_saveVFolder = true;

    /**
     * Cached data.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Search queries.
     *
     * Format:
     * <pre>
     * 'id' => array(
     *     'c' => (array) List of search criteria (the IMP-specific data
     *            structure that allows recreation of the search query on the
     *            search page). For virtual folders, this data is stored in
     *            the preferences,
     *     'f' => (array) List of folders to search,
     *     'l' => (string) Description (label) of search,
     *     'q' => (Horde_Imap_Client_Search_Query) [serialized],
     *     'v' => (boolean) True if this is a Virtual Folder
     * )
     * </pre>
     *
     * The object properties for the 'c' (search criteria) object:
     * <pre>
     * 't' - (string) 'Type' - The criteria type
     *       Values: Keys from self::searchFields(), 'flag', and 'or'.
     * 'v' - (mixed) 'Value' - The data used to build the search
     *       'header' - (string) The value to search for in the header
     *       'customhdr' - (object) Contains 2 elements:
     *                     'h' - (string) The header name
     *                     's' - (string) The search string
     *       'body' - (string) The value to search for in the body
     *       'text' - (string) The value to search for in the entire
     *                message
     *       'date' - (object) Contains 3 elements:
     *                'y' - (integer) The search year
     *                'm' - (integer) The search month (is 1 less than
     *                      the actual month)
     *                'd' - (integer) The search day
     *       'within' - (object) Contains 2 elements:
     *                  'l' - (string) The length of time. Either 'y'
     *                        (years), 'm' (months), or 'd' (days)
     *                  'v' - (integer) The length of time
     *       'size' - (integer) The search size in bytes
     *       'flag' - (string) The flag to search for
     * 'n' - (boolean) 'Not' - Should we do a not search?
     *       Only used for the following types: header, customhdr, body, text
     * </pre>
     *
     * @var array
     */
    protected $_search = array();

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
        $this->changed = true;
    }

    /**
     * Initialize search data for a session.
     *
     * @param boolean $no_vf  Don't readd the Virtual Folders.
     */
    public function init($no_vf = false)
    {
        if (!$no_vf) {
            $imaptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
            foreach ($this->_getVFolderList() as $key => $val) {
                if (!empty($val['v']) &&
                    !$this->isEditableVFolder($key)) {
                    $imaptree->insertVFolders(array($key => $val['l']));
                    unset($val['c']);
                    $this->_search[$key] = $val;
                    $this->changed = true;
                }
            }
        }

        $this->createVINBOXFolder();
        $this->createVTrashFolder();
    }

    /**
     * Return the base search fields.
     *
     * @return array  The base search fields.
     */
    public function searchFields()
    {
        return array(
            'from' => array(
                'label' => _("From"),
                'type' => 'header',
                'not' => true
            ),
            'to' => array(
                'label' => _("To"),
                'type' => 'header',
                'not' => true
            ),
            'cc' => array(
                'label' => _("Cc"),
                'type' => 'header',
                'not' => true
            ),
            'bcc' => array(
                'label' => _("Bcc"),
                'type' => 'header',
                'not' => true
            ),
            'subject' => array(
                'label' => _("Subject"),
                'type' => 'header',
                'not' => true
            ),
            'customhdr' => array(
                'label' => _("Custom Header"),
                'type' => 'customhdr',
                'not' => true
            ),
            'body' => array(
               'label' => _("Body"),
               'type' => 'body',
               'not' => true
            ),
            'text' => array(
               'label' => _("Entire Message"),
               'type' => 'text',
               'not' => true
            ),
            'date_on' => array(
                'label' => _("Date Equals (=)"),
                'type' => 'date',
                'not' => true
            ),
            'date_until' => array(
                'label' => _("Date Until (<)"),
                'type' => 'date',
                'not' => true
            ),
            'date_since' => array(
                'label' => _("Date Since (>=)"),
                'type' => 'date',
                'not' => true
            ),
            'older' => array(
                'label' => _("Older Than"),
                'type' => 'within',
                'not' => true
            ),
            'younger' => array(
                'label' => _("Younger Than"),
                'type' => 'within',
                'not' => true
            ),
            // Displayed in KB, but stored internally in bytes
            'size_smaller' => array(
                'label' => _("Size (KB) <"),
                'type' => 'size',
                'not' => false
            ),
            // Displayed in KB, but stored internally in bytes
            'size_larger' => array(
                'label' => _("Size (KB) >"),
                'type' => 'size',
                'not' => false
            ),
        );
    }

    /**
     * Return the base flag fields.
     *
     * @return array  The base flag fields.
     */
    public function flagFields()
    {
        $flags = array();
        $flist = $GLOBALS['injector']->getInstance('IMP_Imap_Flags')->getFlagList(null);

        for ($i = 0, $cnt = count($flist['set']); $i < $cnt; ++$i) {
            $flags[$flist['set'][$i]['f']] = $flist['set'][$i]['l'];
            $flags[$flist['unset'][$i]['f']] = sprintf(_("Not %s"), $flist['unset'][$i]['l']);
        }

        return $flags;
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

        if (empty($this->_search[$id])) {
            return $sorted;
        }

        /* Prepare the search query. */
        $query = unserialize($this->_search[$id]['q']);
        if (!empty($ob)) {
            $query->andSearch(array($ob));
        }

        /* How do we want to sort results? */
        $sortpref = IMP::getSort(null, true);
        if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
            $sortpref['by'] = $GLOBALS['prefs']->getValue('sortdate');
        }

        foreach ($this->_search[$id]['f'] as $val) {
            $results = $this->imapSearch($val, $query, array('reverse' => $sortpref['dir'], 'sort' => array($sortpref['by'])));
            $sorted->add($val, $results['sort']);
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
    public function runSearchQuery($query, $mailbox, $sortby = null,
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
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb();

        /* If doing a from/to search, use display sorting if possible.
         * Although there is a fallback to a PHP-based display sort, for
         * performance reasons only do a display sort if it is supported
         * on the server. */
        if (($_SESSION['imp']['protocol'] == 'imap') && !empty($opts['sort'])) {
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
     * @param object $query    The search query object
     *                         (Horde_Imap_Client_Search_Query).
     * @param array $folders   The list of folders to search.
     * @param array $criteria  The search criteria array.
     * @param string $label    The label to use for the search results.
     * @param string $id       The query id (otherwise, one is
     *                         automatically generated).
     *
     * @return string  Returns the search query id.
     */
    public function createSearchQuery($query, $folders, $criteria, $label,
                                      $id = null)
    {
        $id = is_null($id)
            ? strval(new Horde_Support_Randomid())
            : $this->_strip($id);

        $this->_search[$id] = array(
            'c' => $criteria,
            'f' => $folders,
            'l' => $label,
            'q' => serialize($query),
            'v' => false
        );
        $this->changed = true;

        return $id;
    }

    /**
     * Deletes an IMAP search query.
     *
     * @param string $id          The search query id.
     * @param boolean $no_delete  Don't delete the entry in the tree object.
     *
     * @return string  Returns the search query id.
     */
    public function deleteSearchQuery($id, $no_delete = false)
    {
        $id = $this->_strip($id);
        $is_vfolder = $this->isVFolder($id);
        unset($this->_search[$id]);
        $this->changed = true;

        if ($is_vfolder) {
            $vfolders = $this->_getVFolderList();
            unset($vfolders[$id]);
            $this->_saveVFolderList($vfolders);

            if (!$no_delete) {
                $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->delete($id);
            }
        }
    }

    /**
     * Retrieves the previously stored search criteria information.
     *
     * @param string $id  The search query id.
     *
     * @return array  The array necessary to rebuild the search UI page.
     */
    public function getCriteria($id)
    {
        $id = $this->_strip($id);
        if (isset($this->_search[$id]['c'])) {
            return $this->_search[$id]['c'];
        }

        if ($this->isVFolder($id)) {
            $vlist = $this->_getVFolderList();
            return $vlist[$id]['c'];
        }

        return array();
    }

    /**
     * Generates the label to use for search results.
     *
     * @param string $id  The search query id.
     *
     * @return string  The search results label.
     */
    public function getLabel($id)
    {
        $id = $this->_strip($id);
        return isset($this->_search[$id]['l'])
            ? $this->_search[$id]['l']
            : '';
    }

    /**
     * Obtains the list of virtual folders for the current user.
     *
     * @return array  The list of virtual folders.
     */
    protected function _getVFolderList()
    {
        if (!isset($this->_cache['vfolder'])) {
            if ($vf = $GLOBALS['prefs']->getValue('vfolder')) {
                $vf = @unserialize($vf);
            }

            if (empty($vf) || !is_array($vf)) {
                $vf = array();
            }

            $this->_cache['vfolder'] = $vf;
        }

        return $this->_cache['vfolder'];
    }

    /**
     * Saves the list of virtual folders for the current user.
     *
     * @param array  The virtual folder list.
     */
    protected function _saveVFolderList($vfolder)
    {
        $GLOBALS['prefs']->setValue('vfolder', serialize($vfolder));
        $this->_cache['vfolder'] = $vfolder;
    }

    /**
     * Add a virtual folder for the current user.
     *
     * @param object $query   The search query object
     *                        (Horde_Imap_Client_Search_Query).
     * @param array $folders  The list of folders to search.
     * @param array $search   The search array used to build the search UI
     *                        screen.
     * @param string $label   The label to use for the search results.
     * @param string $id      The virtual folder id.
     *
     * @return string  The virtual folder ID.
     */
    public function addVFolder($query, $folders, $search, $label, $id = null)
    {
        $id = $this->createSearchQuery($query, $folders, $search, $label, $id);
        $this->_search[$id]['v'] = true;
        $this->changed = true;

        if ($this->_saveVFolder) {
            $vfolders = $this->_getVFolderList();
            $vfolders[$id] = $this->_search[$id];
            $this->_saveVFolderList($vfolders);
        }

        $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->insertVFolders(array($id => $label));

        return $id;
    }

    /**
     * Add a virtual trash folder for the current user.
     */
    public function createVTrashFolder()
    {
        /* Delete the current Virtual Trash folder, if it exists. */
        $vtrash_id = $GLOBALS['prefs']->getValue('vtrash_id');
        if (!empty($vtrash_id)) {
            $this->deleteSearchQuery($vtrash_id, true);
        }

        if (!$GLOBALS['prefs']->getValue('use_vtrash')) {
            return;
        }

        /* Create Virtual Trash with new folder list. */
        $flist = array_keys(iterator_to_array($GLOBALS['injector']->getInstance('IMP_Imap_Tree')));

        $query = new Horde_Imap_Client_Search_Query();
        $query->flag('\\deleted', true);
        $label = _("Virtual Trash");

        $this->_saveVFolder = false;
        if (empty($vtrash_id)) {
            $vtrash_id = $this->addVFolder($query, $flist, array(), $label);
            $GLOBALS['prefs']->setValue('vtrash_id', $vtrash_id);
        } else {
            $this->addVFolder($query, $flist, array(), $label, $vtrash_id);
        }
        $this->_saveVFolder = true;
    }

    /**
     * Determines whether a virtual folder ID is the Virtual Trash Folder.
     *
     * @param string $id  The search query id.
     *
     * @return boolean  True if the ID is the Virtual Trash folder.
     */
    public function isVTrashFolder($id)
    {
        $vtrash_id = $GLOBALS['prefs']->getValue('vtrash_id');
        return (!empty($vtrash_id) && ($this->_strip($id) == $vtrash_id));
    }

    /**
     * Add a virtual INBOX folder for the current user.
     */
    public function createVINBOXFolder()
    {
        /* Delete the current Virtual Inbox folder, if it exists. */
        $vinbox_id = $GLOBALS['prefs']->getValue('vinbox_id');
        if (!empty($vinbox_id)) {
            $this->deleteSearchQuery($vinbox_id, true);
        }

        if (!$GLOBALS['prefs']->getValue('use_vinbox')) {
            return;
        }

        /* Create Virtual INBOX with nav_poll list. */
        $flist = $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->getPollList();

        $query = new Horde_Imap_Client_Search_Query();
        $query->flag('\\seen', false);
        $query->flag('\\deleted', false);
        $label = _("Virtual INBOX");

        $this->_saveVFolder = false;
        if (empty($vinbox_id)) {
            $vinbox_id = $this->addVFolder($query, $flist, array(), $label);
            $GLOBALS['prefs']->setValue('vinbox_id', $vinbox_id);
        } else {
            $this->addVFolder($query, $flist, array(), $label, $vinbox_id);
        }
        $this->_saveVFolder = true;
    }

    /**
     * Determines whether a virtual folder ID is the Virtual INBOX Folder.
     *
     * @param string $id  The search query id.
     *
     * @return boolean  True if the ID is the Virtual INBOX folder.
     */
    public function isVINBOXFolder($id)
    {
        $vinbox_id = $GLOBALS['prefs']->getValue('vinbox_id');
        return (!empty($vinbox_id) && ($this->_strip($id) == $vinbox_id));
    }

    /**
     * Is a mailbox an editable Virtual Folder?
     *
     * @param string $id  The search query id.
     *
     * @return boolean  True if the mailbox is both a virtual folder and can
     *                  be edited.
     */
    public function isEditableVFolder($id)
    {
        $id = $this->_strip($id);
        return ($this->isVFolder($id) &&
                !$this->isVTrashFolder($id) &&
                !$this->isVINBOXFolder($id));
    }

    /**
     * Return a list of queryies.
     *
     * @param integer $mask   A bitmask of the query types to return.
     *                        IMP_Search::LIST_SEARCH,
     *                        IMP_Search::LIST_VFOLDER, and/or
     *                        IMP_Search::NO_BASIC_SEARCH.
     * @param boolean $label  If true, returns the label. Otherwise, returns
     *                        a textual representation.
     *
     * @return array  An array with the folder IDs as the key and the labels
     *                as the value.
     */
    public function listQueries($mask = null, $label = true)
    {
        $folders = array();

        if (empty($this->_search)) {
            return $folders;
        }

        if (is_null($mask)) {
            $mask = self::LIST_SEARCH | self::LIST_VFOLDER;
        }

        foreach ($this->_search as $key => $val) {
            if ((($mask & self::LIST_VFOLDER) && !empty($val['v'])) ||
                (($mask & self::LIST_SEARCH) && empty($val['v'])) &&
                (!($mask & self::NO_BASIC_SEARCH) ||
                 ($key != self::BASIC_SEARCH))) {
                $folders[$key] = $label
                    ? $this->getLabel($key)
                    : $this->searchQueryText($key);
            }
        }

        if ($label) {
            natcasesort($folders);
            return $folders;
        }

        return array_reverse($folders, true);
    }

    /**
     * Get the list of searchable folders for the given search query.
     *
     * @param string $id  The search query id.
     *
     * @return array  The list of searchable folders.
     */
    public function getSearchFolders($id)
    {
        $id = $this->_strip($id);
        return isset($this->_search[$id]['f'])
            ? $this->_search[$id]['f']
            : array();
    }

    /**
     * Return search query text representation for a given search ID.
     *
     * @param string $id  The search query id.
     *
     * @return array  The textual description of the search.
     */
    public function searchQueryText($id)
    {
        $id = $this->_strip($id);

        if (empty($this->_search[$id])) {
            return '';
        } elseif ($this->isVINBOXFolder($id) || $this->isVTrashFolder($id)) {
            return $this->_search[$id]['l'];
        }

        $flagfields = $this->flagFields();
        $searchfields = $this->searchFields();
        $text = '';
        $criteria = $this->getCriteria($id);

        $text = _("Search") . ' ';
        $text_array = array();
        foreach ($criteria as $rule) {
            $field = $rule->t;

            switch ($field) {
            case 'flag':
                if (isset($flagfields[$rule->v])) {
                    $text_array[] = sprintf(_("flagged \"%s\""), $flagfields[$rule->v]);
                }
                break;

            case 'or':
                $text .= implode(' ' . _("and") . ' ', $text_array) . ' ' . _("OR") . ' ';
                $text_array = array();
                break;

            default:
                switch ($searchfields[$field]['type']) {
                case 'customhdr':
                    $text_array[] = sprintf("%s for '%s'", $rule->v->h, ((!empty($rule->n)) ? _("not") . ' ' : '') . $rule->v->s);
                    break;

                case 'date':
                    $date_ob = new Horde_Date($rule->v);
                    $text_array[] = sprintf("%s '%s'", $searchfields[$field]['label'], $date_ob->strftime("%x"));
                    break;

                case 'within':
                    $text_array[] = sprintf("%s %u %s", $searchfields[$field]['label'], $rule->v->v, $rule->v->l == 'y' ? _("years") : ($rule->v->l == 'm' ? _("months") : _("days")));
                    break;

                case 'size':
                    $text_array[] = $searchfields[$field]['label'] . ' ' . ($rule->v / 1024);
                    break;

                default:
                    $text_array[] = sprintf("%s for '%s'", $searchfields[$field]['label'], ((!empty($rule->n)) ? _("not") . ' ' : '') . $rule->v);
                    break;
                }
            }
        }

        return $text . implode(' ' . _("and") . ' ', $text_array) . ' ' . _("in") . ' ' . implode(', ', $this->getSearchFolders($id));
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
        return Horde::url('search.php')->add(array('edit_query' => $this->createSearchID($this->_strip($id))));
    }

    /**
     * Returns a link to delete a given search query.
     *
     * @param string $id  The search query id.
     *
     * @return Horde_Url  The URL to allow deletion of the search query.
     */
    public function deleteUrl($id)
    {
        return Horde::url('folders.php')->add(array(
            'actionID' => 'delete_search_query',
            'folders_token' => Horde::getRequestToken('imp.folders'),
            'queryid' => $this->createSearchID($this->_strip($id))
        ));
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
     * Is the given mailbox a virtual folder?
     *
     * @param string $id  The search query id.
     *
     * @return boolean  Whether the given mailbox name is a virtual folder.
     */
    public function isVFolder($id)
    {
        return !empty($this->_search[$this->_strip($id)]['v']);
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
    public function createSearchID($id)
    {
        return self::MBOX_PREFIX . $this->_strip($id);
    }

}
