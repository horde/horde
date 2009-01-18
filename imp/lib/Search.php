<?php
/**
 * The IMP_Search:: class contains all code related to mailbox searching
 * in IMP.
 *
 * The class uses the $_SESSION['imp']['search'] variable to store information
 * across page accesses. The format of that entry is as follows:
 *
 * $_SESSION['imp']['search'] = array(
 *     'q' => array(
 *         'id_1' => array(
 *             'query' => Horde_Imap_Client_Search_Query object (serialized),
 *             'folders' => array (List of folders to search),
 *             'uiinfo' => array (Info used by search.php to render page.
 *                                For virtual folders, this data is stored
 *                                in the preferences),
 *             'label' => string (Description of search),
 *             'vfolder' => boolean (True if this is a Virtual Folder)
 *         ),
 *         'id_2' => array(
 *             ....
 *         ),
 *         ....
 *     ),
 *     'vtrash_id' => string (The Virtual Trash query ID),
 *     'vinbox_id' => string (The Virtual Inbox query ID)
 * );
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Search
{
    /* Defines used to determine what kind of field query we are dealing
     * with. */
    const HEADER = 1;
    const BODY = 2;
    const DATE = 3;
    const TEXT = 4;
    const SIZE = 5;

    /* Defines used to identify the flag input. */
    const FLAG_NOT = 0;
    const FLAG_HAS = 1;

    /* Defines used to identify whether to show unsubscribed folders. */
    const SHOW_UNSUBSCRIBED = 0;
    const SHOW_SUBSCRIBED_ONLY = 1;

    /**
     * The ID of the current search query in use.
     *
     * @var string
     */
    protected $_id = null;

    /**
     * Save Virtual Folder information when adding entries?
     *
     * @var boolean
     */
    protected $_saveVFolder = true;

    /**
     * Constructor.
     *
     * @param array $params  Available parameters:
     * <pre>
     * 'id'  --  The ID of the search query in use.
     * </pre>
     */
    function __construct($params = array())
    {
        if (!empty($params['id'])) {
            $this->_id = $this->_strip($params['id']);
        }
    }

    /**
     * Set up IMP_Search variables for the current session.
     *
     * @param boolean $no_vf  Don't readd the Virtual Folders.
     */
    public function sessionSetup($no_vf = false)
    {
        if (!isset($_SESSION['imp']['search'])) {
            $_SESSION['imp']['search'] = array('q' => array());
        }
        if (!$no_vf) {
            $imaptree = &IMP_IMAP_Tree::singleton();
            foreach ($this->_getVFolderList() as $key => $val) {
                if (!empty($val['vfolder']) &&
                    !$this->isVTrashFolder($key) &&
                    !$this->isVINBOXFolder($key)) {
                    $imaptree->insertVFolders(array($key => $val['label']));
                    unset($val['uiinfo']);
                    $_SESSION['imp']['search']['q'][$key] = $val;
                }
            }
        }
        $this->createVINBOXFolder();
        $this->createVTrashFolder();
    }

    /**
     * Run a search.
     *
     * @param object $ob  An optional search query to add (via 'AND') to the
     *                    active search (Horde_Imap_Client_Search_Query).
     * @param string $id  The search query id to use (by default, will use the
     *                    current ID set in the object).
     *
     * @return array  The sorted list.
     */
    public function runSearch($ob, $id = null)
    {
        $id = $this->_strip($id);
        $mbox = '';
        $sorted = array();

        if (empty($_SESSION['imp']['search']['q'][$id])) {
            return $sorted;
        }
        $search = &$_SESSION['imp']['search']['q'][$id];

        /* Prepare the search query. */
        $query = unserialize($search['query']);
        if (!empty($ob)) {
            $query->andSearch(array($ob));
        }

        /* How do we want to sort results? */
        $sortpref = IMP::getSort();
        if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
            $sortpref['by'] = Horde_Imap_Client::SORT_DATE;
        }

        foreach ($search['folders'] as $val) {
            try {
                $results = $GLOBALS['imp_imap']->ob->search($val, $query, array('reverse' => $sortpref['dir'], 'sort' => array($sortpref['by'])));
                foreach ($results['sort'] as $val2) {
                    $sorted[] = $val2 . IMP::IDX_SEP . $val;
                }
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        return $sorted;
    }

    /**
     * Run a search query not stored in the current session.  Allows custom
     * queries with custom sorts to be used without affecting cached
     * mailboxes.
     *
     * @param object $query    The search query.
     * @param string $mailbox  The mailbox to search.
     * @param integer $sortby  The sort criteria.
     * @param integer $sortdir The sort directory.
     *
     * @return array  The sorted list.
     */
    public function runSearchQuery($query, $mailbox, $sortby = null,
                                   $sortdir = null)
    {
        try {
            $results = $GLOBALS['imp_imap']->ob->search($mailbox, $query, array('reverse' => $sortdir, 'sort' => array($sortby)));
            return $results['sort'];
        } catch (Horde_Imap_Client_Exception $e) {
            return array();
        }
    }

    /**
     * Creates the IMAP search query in the IMP session.
     *
     * @param object $query   The search query object
     *                        (Horde_Imap_Client_Search_Query).
     * @param array $folders  The list of folders to search.
     * @param array $search   The search array used to build the search UI
     *                        screen.
     * @param string $label   The label to use for the search results.
     * @param string $id      The query id to use (or else one is
     *                        automatically generated).
     *
     * @return string  Returns the search query id.
     */
    public function createSearchQuery($query, $folders, $search, $label,
                                      $id = null)
    {
        $id = is_null($id) ? uniqid(mt_rand()) : $this->_strip($id);
        $_SESSION['imp']['search']['q'][$id] = array(
            'query' => serialize($query),
            'folders' => $folders,
            'uiinfo' => $search,
            'label' => $label,
            'vfolder' => false
        );
        return $id;
    }

    /**
     * Deletes an IMAP search query.
     *
     * @param string $id          The search query id to use (by default, will
     *                            use the current ID set in the object).
     * @param boolean $no_delete  Don't delete the entry in the tree object.
     *
     * @return string  Returns the search query id.
     */
    public function deleteSearchQuery($id = null, $no_delete = false)
    {
        $id = $this->_strip($id);
        $is_vfolder = !empty($_SESSION['imp']['search']['q'][$id]['vfolder']);
        unset($_SESSION['imp']['search']['q'][$id]);

        if ($is_vfolder) {
            $vfolders = $this->_getVFolderList();
            unset($vfolders[$id]);
            $this->_saveVFolderList($vfolders);
            if (!$no_delete) {
                $imaptree = &IMP_IMAP_Tree::singleton();
                $imaptree->delete($id);
            }
        }
    }

    /**
     * Retrieves the previously stored search UI information.
     *
     * @param string $id  The search query id to use (by default, will use
     *                    the current ID set in the object).
     *
     * @return array  The array necessary to rebuild the search UI page.
     */
    public function retrieveUIQuery($id = null)
    {
        $id = $this->_strip($id);
        if (isset($_SESSION['imp']['search']['q'][$id]['uiinfo'])) {
            return $_SESSION['imp']['search']['q'][$id]['uiinfo'];
        }

        if ($this->isVFolder($id)) {
            $vlist = $this->_getVFolderList();
            return $vlist[$id]['uiinfo'];
        }

        return array();
    }

    /**
     * Generates the label to use for search results.
     *
     * @param string $id  The search query id to use (by default, will use
     *                    the current ID set in the object).
     *
     * @return string  The search results label.
     */
    public function getLabel($id = null)
    {
        $id = $this->_strip($id);
        return (isset($_SESSION['imp']['search']['q'][$id]['label']))
            ? $_SESSION['imp']['search']['q'][$id]['label']
            : '';
    }

    /**
     * Obtains the list of virtual folders for the current user.
     *
     * @return array  The list of virtual folders.
     */
    protected function _getVFolderList()
    {
        static $vfolder;

        if (isset($vfolder)) {
            return $vfolder;
        }

        $vfolder = $GLOBALS['prefs']->getValue('vfolder');
        if (empty($vfolder)) {
            return array();
        }

        $old_error = error_reporting(0);
        $vfolder = unserialize($vfolder);
        error_reporting($old_error);

        if (!is_array($vfolder)) {
            $vfolder = array();
        }

        return $vfolder;
    }

    /**
     * Saves the list of virtual folders for the current user.
     *
     * @param array  The virtual folder list.
     */
    protected function _saveVFolderList($vfolder)
    {
        $GLOBALS['prefs']->setValue('vfolder', serialize($vfolder));
    }

    /**
     * Add a virtual folder for the current user.
     *
     * @param object $query  The search query object
     *                       (Horde_Imap_Client_Search_Query).
     * @param array $folders The list of folders to search.
     * @param array $search  The search array used to build the search UI
     *                       screen.
     * @param string $label  The label to use for the search results.
     * @param string $id     The virtual folder id.
     *
     * @return string  The virtual folder ID.
     */
    public function addVFolder($query, $folders, $search, $label, $id = null)
    {
        $id = $this->createSearchQuery($query, $folders, $search, $label, $id);
        $_SESSION['imp']['search']['q'][$id]['vfolder'] = true;
        if ($this->_saveVFolder) {
            $vfolders = $this->_getVFolderList();
            $vfolders[$id] = $_SESSION['imp']['search']['q'][$id];
            $this->_saveVFolderList($vfolders);
        }

        $imaptree = &IMP_IMAP_Tree::singleton();
        $imaptree->insertVFolders(array($id => $label));

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
        $imp_folder = &IMP_Folder::singleton();
        $fl = $imp_folder->flist();
        $flist = array();
        foreach ($fl as $mbox) {
            if (!empty($mbox['val'])) {
                $flist[] = $mbox['val'];
            }
        }
        array_unshift($flist, 'INBOX');

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
        $_SESSION['imp']['search']['vtrash_id'] = $vtrash_id;
    }

    /**
     * Determines whether a virtual folder ID is the Virtual Trash Folder.
     *
     * @param string $id  The search query id to use (by default, will use
     *                    the current ID set in the object).
     *
     * @return boolean  True if the virutal folder ID is the Virtual Trash
     *                  folder.
     */
    public function isVTrashFolder($id = null)
    {
        $id = $this->_strip($id);
        $vtrash_id = $GLOBALS['prefs']->getValue('vtrash_id');
        return (!empty($vtrash_id) && ($id == $vtrash_id));
    }

    /**
     * Add a virtual INBOX folder for the current user.
     */
    public function createVINBOXFolder()
    {
        /* Initialize IMP_IMAP_Tree. */
        $imaptree = &IMP_IMAP_Tree::singleton();

        /* Delete the current Virtual Inbox folder, if it exists. */
        $vinbox_id = $GLOBALS['prefs']->getValue('vinbox_id');
        if (!empty($vinbox_id)) {
            $this->deleteSearchQuery($vinbox_id, true);
        }

        if (!$GLOBALS['prefs']->getValue('use_vinbox')) {
            return;
        }

        /* Create Virtual INBOX with nav_poll list. Filter out any nav_poll
         * entries that don't exist. Sort the list also. */
        $flist = $imaptree->getPollList(true, true);

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
        $_SESSION['imp']['search']['vinbox_id'] = $vinbox_id;
    }

    /**
     * Determines whether a virtual folder ID is the Virtual INBOX Folder.
     *
     * @param string $id  The search query id to use (by default, will use
     *                    the current ID set in the object).
     *
     * @return boolean  True if the virutal folder ID is the Virtual INBOX
     *                  folder.
     */
    public function isVINBOXFolder($id = null)
    {
        $id = $this->_strip($id);
        $vinbox_id = $GLOBALS['prefs']->getValue('vinbox_id');
        return (!empty($vinbox_id) && ($id == $vinbox_id));
    }

    /**
     * Is the current active folder an editable Virtual Folder?
     *
     * @param string $id  The search query id to use (by default, will use
     *                    the current ID set in the object).
     *
     * @return boolean  True if the current folder is both a virtual folder
     *                  and can be edited.
     */
    public function isEditableVFolder($id = null)
    {
        $id = $this->_strip($id);
        return ($this->isVFolder($id) && !$this->isVTrashFolder($id) && !$this->isVINBOXFolder($id));
    }

    /**
     * Return a list of IDs and query labels, sorted by the label.
     *
     * @param boolean $vfolder  If true, only return Virtual Folders?
     *
     * @return array  An array with the folder IDs as the key and the labels
     *                as the value.
     */
    public function listQueries($vfolder = false)
    {
        $vfolders = array();

        if (empty($_SESSION['imp']['search']['q'])) {
            return $vfolders;
        }

        foreach ($_SESSION['imp']['search']['q'] as $key => $val) {
            if (!$vfolder || !empty($val['vfolder'])) {
                $vfolders[$key] = $this->getLabel($key);
            }
        }
        natcasesort($vfolders);

        return $vfolders;
    }

    /**
     * Get the list of searchable folders for the given search query.
     *
     * @param string $id  The search query id to use (by default, will use
     *                    the current ID set in the object).
     *
     * @return array  The list of searchable folders.
     */
    public function getSearchFolders($id = null)
    {
        $id = $this->_strip($id);
        return (isset($_SESSION['imp']['search']['q'][$id]['folders'])) ? $_SESSION['imp']['search']['q'][$id]['folders'] : array();
    }

    /**
     * Return a list of search queries valid only for the current session
     * (i.e. no virtual folders).
     *
     * @return array  Keys are the search ids, values are a textual
     *                description of the search.
     */
    public function getSearchQueries()
    {
        $retarray = array();

        foreach ($_SESSION['imp']['search']['q'] as $key => $val) {
            if (!$this->isVFolder($key) &&
                ($text = $this->searchQueryText($key))) {
                $retarray[$key] = $text;
            }
        }

        return array_reverse($retarray, true);
    }

    /**
     * Return search query text representation for a given search ID.
     *
     * @param string $id  The search query id to use (by default, will use
     *                    the current ID set in the object).
     *
     * @return array  The textual description of the search.
     */
    public function searchQueryText($id = null)
    {
        $id = $this->_strip($id);

        if (empty($_SESSION['imp']['search']['q'][$id])) {
            return '';
        } elseif ($this->isVINBOXFolder($id) || $this->isVTrashFolder($id)) {
            return $_SESSION['imp']['search']['q'][$id]['label'];
        }

        $flagfields = $this->flagFields();
        $searchfields = $this->searchFields();
        $text = '';
        $uiinfo = $this->retrieveUIQuery($id);

        if (!empty($uiinfo['field'])) {
            $text = _("Search") . ' ';
            $text_array = array();
            foreach ($uiinfo['field'] as $key2 => $val2) {
                if (isset($flagfields[$val2])) {
                    $text_array[] = $flagfields[$val2]['label'];
                } else {
                    switch ($searchfields[$val2]['type']) {
                    case self::DATE:
                        $text_array[] = sprintf("%s '%s'", $searchfields[$val2]['label'], strftime("%x", mktime(0, 0, 0, $uiinfo['date'][$key2]['month'], $uiinfo['date'][$key2]['day'], $uiinfo['date'][$key2]['year'])));
                        break;

                    case self::SIZE:
                        $text_array[] = $searchfields[$val2]['label'] . ' ' . ($uiinfo['text'][$key2] / 1024);
                        break;

                    default:
                        $text_array[] = sprintf("%s for '%s'", $searchfields[$val2]['label'], ((!empty($uiinfo['text_not'][$key2])) ? _("not") . ' ' : '') . $uiinfo['text'][$key2]);
                        break;
                    }
                }
            }
            $text .= implode(' ' . (($uiinfo['match'] == 'and') ? _("and") : _("or")) . ' ', $text_array);
        }

        return $text . ' ' . _("in") . ' ' . implode(', ', $uiinfo['folders']);
    }

    /**
     * Returns a link to edit a given search query.
     *
     * @param string $id  The search query id to use (by default, will use
     *                    the current ID set in the object).
     *
     * @return string  The URL to the search page.
     */
    public function editURL($id = null)
    {
        $id = $this->_strip($id);
        return Util::addParameter(Horde::applicationUrl('search.php'), array('edit_query' => $id));
    }

    /**
     * Returns a link to delete a given search query.
     *
     * @param string $id  The search query id to use (by default, will use
     *                    the current ID set in the object).
     *
     * @return string  The URL to allow deletion of the search query.
     */
    public function deleteURL($id = null)
    {
        $id = $this->_strip($id);
        return Util::addParameter(Horde::applicationUrl('folders.php'),
                                  array('actionID' => 'delete_search_query',
                                        'folders_token' => IMP::getRequestToken('imp.folders'),
                                        'queryid' => $id,
                                  ));
    }

    /**
     * Is the given mailbox a search mailbox?
     *
     * @param string $id  The search query id to use (by default, will use
     *                    the current ID set in the object).
     *
     * @return boolean  Whether the given mailbox name is a search mailbox.
     */
    public function isSearchMbox($id = null)
    {
        return ($id === null) ? !empty($this->_id) : isset($_SESSION['imp']['search']['q'][$this->_strip($id)]);
    }

    /**
     * Is the given mailbox a virtual folder?
     *
     * @param string $id  The search query id to use (by default, will use
     *                    the current ID set in the object).
     *
     * @return boolean  Whether the given mailbox name is a virtual folder.
     */
    public function isVFolder($id = null)
    {
        $id = $this->_strip($id);
        return (!empty($_SESSION['imp']['search']['q'][$id]['vfolder']));
    }

    /**
     * Get the ID for the search mailbox, if we are currently in a search
     * mailbox.
     *
     * @return mixed  The search ID if in a mailbox, else false.
     */
    public function searchMboxID()
    {
        return ($this->_id !== null) ? $this->_id : false;
    }

    /**
     * Strip the identifying label from a mailbox ID.
     *
     * @param string $id  The mailbox query ID.
     *
     * @return string  The virtual folder ID, with any IMP specific identifying
     *                 information stripped off.
     */
    protected function _strip($id)
    {
        return ($id === null) ? $this->_id : ((strpos($id, IMP::SEARCH_MBOX) === 0) ? substr($id, strlen(IMP::SEARCH_MBOX)) : $id);
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
        return IMP::SEARCH_MBOX . $this->_strip($id);
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
                'type' => self::HEADER,
                'not' => true
            ),
            'to' => array(
                'label' => _("To"),
                'type' => self::HEADER,
                'not' => true
            ),
            'cc' => array(
                'label' => _("Cc"),
                'type' => self::HEADER,
                'not' => true
            ),
            'bcc' => array(
                'label' => _("Bcc"),
                'type' => self::HEADER,
                'not' => true
            ),
            'subject' => array(
                'label' => _("Subject"),
                'type' => self::HEADER,
                'not' => true
            ),
            'body' => array(
               'label' => _("Body"),
               'type' => self::BODY,
                'not' => true
            ),
            'text' => array(
               'label' => _("Entire Message"),
               'type' => self::TEXT,
                'not' => true
            ),
            'date_on' => array(
                'label' => _("Date ="),
                'type' => self::DATE,
                'not' => true
            ),
            'date_until' => array(
                'label' => _("Date <"),
                'type' => self::DATE,
                'not' => true
            ),
            'date_since' => array(
                'label' => _("Date >="),
                'type' => self::DATE,
                'not' => true
            ),
            // Displayed in KB, but stored internally in bytes
            'size_smaller' => array(
                'label' => _("Size (KB) <"),
                'type' => self::SIZE,
                'not' => false
            ),
            // Displayed in KB, but stored internally in bytes
            'size_larger' => array(
                'label' => _("Size (KB) >"),
                'type' => self::SIZE,
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
        return array(
            'seen' => array(
                'flag' => '\\seen',
                'label' => _("Seen messages"),
                'type' => self::FLAG_HAS
            ),
            'unseen' => array(
                'flag' => '\\seen',
                'label' => _("Unseen messages"),
                'type' => self::FLAG_NOT
            ),
            'answered' => array(
                'flag' => '\\answered',
                'label' => _("Answered messages"),
                'type' => self::FLAG_HAS
            ),
            'unanswered' => array(
                'flag' => '\\answered',
                'label' => _("Unanswered messages"),
                'type' => self::FLAG_NOT
            ),
            'flagged' => array(
                'flag' => '\\flagged',
                'label' => _("Flagged messages"),
                'type' => self::FLAG_HAS
            ),
            'unflagged' => array(
                'flag' => '\\flagged',
                'label' => _("Unflagged messages"),
                'type' => self::FLAG_NOT
            ),
            'deleted' => array(
                'flag' => '\\deleted',
                'label' => _("Deleted messages"),
                'type' => self::FLAG_HAS
            ),
            'undeleted' => array(
                'flag' => '\\deleted',
                'label' => _("Undeleted messages"),
                'type' => self::FLAG_NOT
            ),
        );
    }

    /**
     * Creates a search query.
     *
     * @param array $uiinfo  A UI info array (see imp/search.php).
     *
     * @return object  A search object (Horde_Imap_Client_Search_Query).
     */
    public function createQuery($search)
    {
        $query = new Horde_Imap_Client_Search_Query();

        $search_array = array();
        $search_fields = $this->searchFields();
        $flag_fields = $this->flagFields();

        foreach ($search['field'] as $key => $val) {
            $ob = new Horde_Imap_Client_Search_Query();

            if (isset($flag_fields[$val])) {
                $ob->flag($flag_fields[$val]['flag'], (bool)$flag_fields[$val]['type']);
                $search_array[] = $ob;
            } else {
                switch ($search_fields[$val]['type']) {
                case self::HEADER:
                    if (!empty($search['text'][$key])) {
                        $ob->headerText($val, $search['text'][$key], $search['text_not'][$key]);
                        $search_array[] = $ob;
                    }
                    break;

                case self::BODY:
                case self::TEXT:
                    if (!empty($search['text'][$key])) {
                        $ob->text($search['text'][$key], $search_fields[$val]['type'] == self::BODY, $search['text_not'][$key]);
                        $search_array[] = $ob;
                    }
                    break;

                case self::DATE:
                    if (!empty($search['date'][$key]['day']) &&
                        !empty($search['date'][$key]['month']) &&
                        !empty($search['date'][$key]['year'])) {
                        $date = new Horde_Date($search['date']);
                        $ob->dateSearch($date, ($val == 'date_on') ? Horde_Imap_Client_Search_Query::DATE_ON : (($val == 'date_until') ? Horde_Imap_Client_Search_Query::DATE_BEFORE : Horde_Imap_Client_Search_Query::DATE_SINCE));
                        $search_array[] = $ob;
                    }
                    break;

                case self::SIZE:
                    if (!empty($search['text'][$key])) {
                        $ob->size(intval($search['text'][$key]), $val == 'size_larger');
                        $search_array[] = $ob;
                    }
                    break;
                }
            }
        }

        /* Search match. */
        if ($search['match'] == 'and') {
            $query->andSearch($search_array);
        } elseif ($search['match'] == 'or') {
            $query->orSearch($search_array);
        }

        return $query;
    }
}
