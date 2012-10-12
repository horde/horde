<?php
/**
 * The IMP_Imap_Tree class provides a tree view of the mailboxes in an
 * IMAP/POP3 repository (a/k/a a folder list; in IMP, folders = collection of
 * mailboxes).  It provides access functions to iterate through this tree and
 * query information about individual mailboxes.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @author   Anil Madhavapeddy <avsm@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 *
 * @property boolean $changed  Has the tree changed?
 * @property integer $unseen  The number of unseen messages counted during the
 *                            last tree iteration.
 */
class IMP_Imap_Tree implements ArrayAccess, Countable, Iterator, Serializable
{
    /* Serialized version. */
    const VERSION = 2;

    /* Constants for mailboxElt attributes. */
    const ELT_NOSELECT = 1;
    const ELT_NAMESPACE = 2;
    const ELT_IS_OPEN = 4;
    const ELT_IS_SUBSCRIBED = 8;
    // Unused constant: 16
    const ELT_IS_POLLED = 32;
    const ELT_NEED_SORT = 64;
    const ELT_VFOLDER = 128;
    const ELT_NONIMAP = 256;
    const ELT_INVISIBLE = 512;

    /* The isOpen() expanded mode constants. */
    const OPEN_NONE = 0;
    const OPEN_ALL = 1;
    const OPEN_USER = 2;

    /* The list filtering constants. */
    const FLIST_NOCONTAINER = 1;
    const FLIST_UNSUB = 2;
    const FLIST_VFOLDER = 4;
    const FLIST_NOCHILDREN = 8;
    const FLIST_EXPANDED = 16;
    const FLIST_ANCESTORS = 32;
    const FLIST_SAMELEVEL = 64;
    const FLIST_NOBASE = 128;
    const FLIST_ASIS = 256;
    const FLIST_NOSPECIALMBOXES = 512;
    const FLIST_POLLED = 1024;

    /* The string used to indicate the base of the tree. This must include
     * null since this is the only 7-bit character not allowed in IMAP
     * mailboxes. */
    const BASE_ELT = "base\0";

    /* Add null to folder key since it allows us to sort by name but
     * never conflict with an IMAP mailbox. */
    const VFOLDER_KEY = "vfolder\0";

    /* Defines used with namespace display. */
    const SHARED_KEY = "shared\0";
    const OTHER_KEY = "other\0";

    /**
     * Track element changes?
     *
     * @var boolean
     */
    public $track = false;

    /**
     * Cached data that is not saved across serialization.
     *
     * @var array
     */
    protected $_cache = array(
        'filter' => array(
            'base' => null,
            'mask' => 0
        )
    );

    /**
     * Tree changed flag.  Set when something in the tree has been altered.
     *
     * @var boolean
     */
    protected $_changed = false;

    /**
     * Location of current element in the tree.
     *
     * @var integer
     */
    protected $_currkey;

    /**
     * Location of current element in the tree.
     *
     * @var string
     */
    protected $_currparent;

    /**
     * The string used for the IMAP delimiter.
     *
     * @var string
     */
    protected $_delimiter;

    /**
     * List of element changes.
     *
     * @var array
     */
    protected $_eltdiff;

    /**
     * The list of namespaces to add to the tree.
     *
     * @var array
     */
    protected $_namespaces;

    /**
     * Parent list.
     *
     * @var array
     */
    protected $_parent;

    /**
     * Show unsubscribed mailboxes?
     *
     * @var boolean
     */
    protected $_showunsub;

    /**
     * Array containing the mailbox tree.
     *
     * @var array
     */
    protected $_tree;

    /**
     * Unseen count.
     *
     * @var array
     */
    protected $_unseen = 0;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'changed':
            return $this->_changed;

        case 'unseen':
            return $this->_unseen;
        }
    }

    /**
     * Initalize the tree.
     */
    public function init()
    {
        global $injector, $prefs, $session;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        $unsubmode = (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS) ||
                      !$prefs->getValue('subscribe') ||
                      $session->get('imp', 'showunsub'));

        /* Reset class variables to the defaults. */
        $this->_changed = true;
        $this->_currkey = $this->_currparent = null;
        $this->_delimiter = null;
        $this->_namespaces = $this->_parent = $this->_tree = array();
        $this->_showunsub = $unsubmode;
        unset($this->_cache['fulllist'], $this->_cache['subscribed']);
        $this->_resetEltDiff();

        /* Don't track initialization. */
        $old_track = $this->track;
        $this->track = false;

        /* Do IMAP specific initialization. */
        if ($imp_imap->imap) {
            $ns = $imp_imap->getNamespaceList();
            $ptr = reset($ns);
            $this->_delimiter = $ptr['delimiter'];
            if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
                $this->_namespaces = $ns;
            }
        }

        /* Create a placeholder element to the base of the tree list so we can
         * keep track of whether the base level needs to be sorted. */
        $this->_tree[self::BASE_ELT] = array(
            'a' => self::ELT_NEED_SORT,
            'v' => self::BASE_ELT
        );

        /* Add INBOX and exit if folders aren't allowed or if we are using
         * POP3. */
        if (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            $this->_insertElt($this->_makeElt('INBOX', self::ELT_IS_SUBSCRIBED));
            return;
        }

        /* Add namespace elements. */
        if ($prefs->getValue('tree_view')) {
            foreach ($this->_namespaces as $val) {
                $type = null;

                switch ($val['type']) {
                case Horde_Imap_Client::NS_OTHER:
                    $type = self::OTHER_KEY;
                    break;

                case Horde_Imap_Client::NS_SHARED:
                    $type = self::SHARED_KEY;
                    break;
                }

                if (!is_null($type) && !isset($this->_tree[$type])) {
                    $this->_insertElt($this->_makeElt(
                        $type,
                        self::ELT_NOSELECT | self::ELT_NAMESPACE | self::ELT_NONIMAP
                    ));
                }
            }
        }

        /* Create the list (INBOX and all other hierarchies). */
        $this->_insert($this->_getList($this->_showunsub), $this->_showunsub ? null : true);

        /* Add virtual folders to the tree. */
        $imp_search = $injector->getInstance('IMP_Search');
        $imp_search->setIteratorFilter(IMP_Search::LIST_VFOLDER);
        foreach ($imp_search as $val) {
            $this->insert($val);
        }

        $this->track = $old_track;
    }

    /**
     * Returns the list of mailboxes on the server.
     *
     * @param boolean $showunsub  Show unsubscribed mailboxes?
     *
     * @return array  See Horde_Imap_Client_Base::listMailboxes().
     * @throws IMP_Imap_Exception
     */
    protected function _getList($showunsub)
    {
        if ($showunsub && isset($this->_cache['fulllist'])) {
            return $this->_cache['fulllist'];
        } elseif (!$showunsub && isset($this->_cache['subscribed'])) {
            return $this->_cache['subscribed'];
        }

        $searches = array();
        foreach (array_keys($this->_namespaces) as $val) {
            $searches[] = $val . '*';
        }

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
        $result = $imp_imap->listMailboxes($searches, $showunsub ? Horde_Imap_Client::MBOX_ALL : Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS, array(
            'attributes' => true,
            'delimiter' => true,
            'sort' => true
        ));

        /* INBOX must always appear. */
        if (empty($result['INBOX'])) {
            $result = $imp_imap->listMailboxes('INBOX', Horde_Imap_Client::MBOX_ALL, array('attributes' => true, 'delimiter' => true)) + $result;
        }

        $tmp = array();
        foreach ($result as $val) {
            $tmp[strval($val['mailbox'])] = $val;
        }
        $this->_cache[$showunsub ? 'fulllist' : 'subscribed'] = $tmp;

        return $result;
    }

    /**
     * Make a single mailbox tree element.
     *
     * @param string $name         The mailbox name.
     * @param integer $attributes  The mailbox's attributes.
     *
     * @return array  An array with the following keys (we use single letters
     *                to save in session storage space):
     *   - a: (integer) Attributes mask.
     *   - c: (integer) Level count.
     *   - p: (string) Parent node.
     *   - v: (string) Value.
     *
     * @throws Horde_Exception
     */
    protected function _makeElt($name, $attributes = 0)
    {
        $elt = array(
            'a' => $attributes,
            'c' => 0,
            'p' => self::BASE_ELT,
            'v' => strval($name)
        );

        /* Check for polled status. */
        $this->_initPollList();
        $this->_setPolled($elt, isset($this->_cache['poll'][$name]));

        /* Check for open status. */
        switch ($GLOBALS['prefs']->getValue('nav_expanded')) {
        case self::OPEN_NONE:
            $open = false;
            break;

        case self::OPEN_ALL:
            $open = true;
            break;

        case self::OPEN_USER:
            $this->_initExpandedList();
            $open = !empty($this->_cache['expanded'][$name]);
            break;
        }
        $this->_setOpen($elt, $open);

        if (is_null($this->_delimiter)) {
            $elt['c'] = 0;
            return $elt;
        }

        $ns_info = $this->_getNamespace($name);
        $delimiter = is_null($ns_info)
            ? $this->_delimiter
            : $ns_info['delimiter'];
        $tmp = explode($delimiter, $name);
        $elt['c'] = count($tmp) - 1;

        try {
            $this->_setInvisible($elt, !Horde::callHook('display_folder', array($elt['v']), 'imp'));
        } catch (Horde_Exception_HookNotSet $e) {}

        if ($elt['c'] != 0) {
            $elt['p'] = implode(is_null($ns_info) ? $this->_delimiter : $ns_info['delimiter'], array_slice($tmp, 0, $elt['c']));
        }

        if (is_null($ns_info)) {
            return $elt;
        }

        switch ($ns_info['type']) {
        case Horde_Imap_Client::NS_PERSONAL:
            /* Strip personal namespace. */
            if (!empty($ns_info['name']) && ($elt['c'] != 0)) {
                --$elt['c'];
                if (strpos($elt['p'], $ns_info['delimiter']) === false) {
                    $elt['p'] = self::BASE_ELT;
                } elseif (strpos($elt['v'], $ns_info['name'] . 'INBOX' . $ns_info['delimiter']) === 0) {
                    $elt['p'] = 'INBOX';
                }
            }
            break;

        case Horde_Imap_Client::NS_OTHER:
        case Horde_Imap_Client::NS_SHARED:
            if (substr($ns_info['name'], 0, -1 * strlen($ns_info['delimiter'])) == $elt['v']) {
                $elt['a'] = self::ELT_NOSELECT | self::ELT_NAMESPACE;
            }

            if ($GLOBALS['prefs']->getValue('tree_view')) {
                /* Don't add namespace element to tree. */
                if ($this->isNamespace($elt)) {
                    return false;
                }

                if ($elt['c'] == 1) {
                    $elt['p'] = ($ns_info['type'] == Horde_Imap_Client::NS_OTHER)
                        ? self::OTHER_KEY
                        : self::SHARED_KEY;
                }
            }
            break;
        }

        return $elt;
    }

    /**
     * Expand a mailbox.
     *
     * @param string $mbox        The mailbox name to expand.
     * @param boolean $expandall  Expand all subfolders?
     */
    public function expand($mbox, $expandall = false)
    {
        $mbox = $this->_convertName($mbox);

        if (!isset($this->_tree[$mbox])) {
            return;
        }
        $elt = &$this->_tree[$mbox];

        if ($this->hasChildren($elt)) {
            if (!$this->isOpen($elt)) {
                $this->_changed = true;
                $this->_setOpen($elt, true);
            }

            /* Expand all children beneath this one. */
            if ($expandall && !empty($this->_parent[$mbox])) {
                foreach ($this->_parent[$mbox] as $val) {
                    $this->expand($this->_tree[$val]['v'], true);
                }
            }
        }
    }

    /**
     * Collapse a mailbox.
     *
     * @param string $mbox  The mailbox name to collapse.
     */
    public function collapse($mbox)
    {
        $mbox = $this->_convertName($mbox);

        if (isset($this->_tree[$mbox])) {
            $this->_changed = true;
            $this->_setOpen($this->_tree[$mbox], false);
        }
    }

    /**
     * Insert a mailbox/virtual folder into the tree.
     *
     * @param mixed $id  The name of the mailbox (or a list of mailboxes)
     *                   to add. Can also be a virtual folder object.
     */
    public function insert($id)
    {
        if (!is_array($id)) {
            $id = array($id);
        }

        $to_insert = array();

        foreach ($id as $val) {
            /* Convert virtual folders to internal representation. */
            if ($val instanceof IMP_Search_Vfolder) {
                if (!$val->enabled) {
                    continue;
                }
                $val = self::VFOLDER_KEY . $this->_delimiter . $val;
            }

            /* Virtual Folders. */
            if (strpos($val, self::VFOLDER_KEY) === 0) {
                if (!isset($this->_tree[$val])) {
                    if (!isset($this->_tree[self::VFOLDER_KEY])) {
                        $elt = $this->_makeElt(self::VFOLDER_KEY, self::ELT_VFOLDER | self::ELT_NOSELECT | self::ELT_NONIMAP);
                        $this->_insertElt($elt);
                    }

                    $elt = $this->_makeElt($val, self::ELT_VFOLDER | self::ELT_IS_SUBSCRIBED);
                    $elt['v'] = Horde_String::substr($val, Horde_String::length(self::VFOLDER_KEY) + Horde_String::length($this->_delimiter));
                    $this->_insertElt($elt);
                }
            } else {
                $to_insert[] = Horde_Imap_Client_Mailbox::get($val);
            }
        }

        if (!empty($to_insert)) {
            /* We want to add from the BASE of the tree up for efficiency
             * sake. */
            $this->_sortList($to_insert);

            try {
                $this->_insert($GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->listMailboxes($to_insert, Horde_Imap_Client::MBOX_ALL, array(
                    'attributes' => true,
                    'delimiter' => true,
                    'sort' => true
                )));
            } catch (IMP_Imap_Exception $e) {}
        }
    }

    /**
     * Insert mailbox elements into the tree.
     *
     * @param array $elts   See Horde_Imap_Client_Base::listMailboxes().
     * @param boolean $sub  If set, the list of $elts are known to be either
     *                      all subscribed (true) or unsubscribed (false). If
     *                      null, subscribed status must be looked up on the
     *                      server.
     */
    protected function _insert($elts, $sub = null)
    {
        $sub_pref = $GLOBALS['prefs']->getValue('subscribe');

        foreach ($elts as $val) {
            $key = strval($val['mailbox']);
            if (isset($this->_tree[$key]) ||
                in_array('\nonexistent', $val['attributes'])) {
                continue;
            }

            /* Break apart the name via the delimiter and go step by
             * step through the name to make sure all subfolders exist
             * in the tree. */
            $parts = explode($val['delimiter'], $key);
            $parts[0] = $this->_convertName($parts[0]);
            for ($i = 1, $p_count = count($parts); $i <= $p_count; ++$i) {
                $part = implode($val['delimiter'], array_slice($parts, 0, $i));

                if (!isset($this->_tree[$part])) {
                    $attributes = 0;

                    /* Set subscribed values. We know the mailbox is
                     * subscribed, without query of the IMAP server, in the
                     * following situations:
                     * + Subscriptions are turned off.
                     * + $sub is true.
                     * + Mailbox is INBOX.
                     * + Mailbox has the \subscribed attribute set. */
                    if (!$sub_pref ||
                        (($i == $p_count) &&
                         (($sub === true) ||
                          ($key == 'INBOX') ||
                          in_array('\subscribed', $val['attributes'])))) {
                        $attributes |= self::ELT_IS_SUBSCRIBED;
                    } elseif (is_null($sub) && ($i == $p_count)) {
                        $this->_getList(false);
                        if (isset($this->_cache['subscribed'][$part])) {
                            $attributes |= self::ELT_IS_SUBSCRIBED;
                        }
                    }

                    if (($i != $p_count) ||
                        in_array('\noselect', $val['attributes'])) {
                        $attributes |= self::ELT_NOSELECT;
                    }

                    $this->_insertElt($this->_makeElt($part, $attributes));
                }
            }
        }
    }

    /**
     * Insert an element into the tree.
     *
     * @param array $elt  The element to insert. The key in the tree is the
     *                    'v' (value) element of the element.
     */
    protected function _insertElt($elt)
    {
        if (!$elt || isset($this->_tree[$elt['v']])) {
            return;
        }

        // UW fix - it may return both 'foo' and 'foo/' as mailbox names.
        // Only add one of these (without the namespace character) to
        // the tree.  See Ticket #5764.
        $ns_info = $this->_getNamespace($elt['v']);
        if (isset($this->_tree[rtrim($elt['v'], is_null($ns_info) ? $this->_delimiter : $ns_info['delimiter'])])) {
            return;
        }

        $this->_changed = true;

        $prev = $this->track
            ? $this->hasChildren($this->_tree[$elt['p']])
            : null;

        /* Set the parent array to the value in $elt['p']. */
        if (empty($this->_parent[$elt['p']])) {
            $this->_parent[$elt['p']] = array();
        }

        $this->_parent[$elt['p']][] = $elt['v'];
        $this->_tree[$elt['v']] = $elt;

        $this->_addEltDiff($elt, 'a');
        if (!is_null($prev) &&
            ($this->hasChildren($this->_tree[$elt['p']]) != $prev)) {
            $this->_addEltDiff($this->_tree[$elt['p']], 'c');
        }

        /* Make sure we are sorted correctly. */
        if (count($this->_parent[$elt['p']]) > 1) {
            $this->_setNeedSort($this->_tree[$elt['p']], true);
        }
    }

    /**
     * Delete an element from the tree.
     *
     * @param mixed $id  The element name or an array of element names.
     *
     * @return boolean  Return true on success, false on error.
     */
    public function delete($id)
    {
        if (is_array($id)) {
            /* We want to delete from the TOP of the tree down to ensure that
             * parents have an accurate view of what children are left. */
            $this->_sortList($id);
            $id = array_reverse($id);

            foreach ($id as $val) {
                $currsuccess = $this->delete($val);
                if (!$currsuccess) {
                    return false;
                }
            }

            return true;
        }

        $id = $this->_convertName($id, true);
        $vfolder_base = ($id == self::VFOLDER_KEY);
        $search_id = $GLOBALS['injector']->getInstance('IMP_Search')->createSearchId($id);

        if ($vfolder_base ||
            (isset($this->_tree[$search_id]) &&
             $this->isVFolder($this->_tree[$search_id]))) {
            if (!$vfolder_base) {
                $id = $search_id;
            }

            $parent = $this->_tree[$id]['p'];
            $this->_addEltDiff($this->_tree[$id], 'd');
            unset($this->_tree[$id]);

            /* Delete the entry from the parent tree. */
            $key = array_search($id, $this->_parent[$parent]);
            unset($this->_parent[$parent][$key]);

            /* Rebuild the parent tree. */
            if (!$vfolder_base && empty($this->_parent[$parent])) {
                $this->delete($parent);
            } else {
                $this->_parent[$parent] = array_values($this->_parent[$parent]);
            }
            $this->_changed = true;

            return true;
        }

        $ns_info = $this->_getNamespace($id);

        if (($id == 'INBOX') ||
            !isset($this->_tree[$id]) ||
            ($id == $ns_info['name'])) {
            return false;
        }

        $this->_changed = true;

        $elt = &$this->_tree[$id];

        /* Delete the entry from the mailbox list cache(s). */
        unset($this->_cache['fulllist'][$id], $this->_cache['subscribed'][$id]);

        /* Do not delete from tree if there are child elements - instead,
         * convert to a container element. */
        if ($this->hasChildren($elt)) {
            $this->_setContainer($elt, true);
            return true;
        }

        $parent = $elt['p'];

        /* Delete the tree entry. */
        $this->_addEltDiff($elt, 'd');
        unset($this->_tree[$id]);

        /* Delete the entry from the parent tree. */
        $key = array_search($id, $this->_parent[$parent]);
        unset($this->_parent[$parent][$key]);

        if (empty($this->_parent[$parent])) {
            /* This mailbox is now completely empty (no children). */
            unset($this->_parent[$parent]);
            if (isset($this->_tree[$parent])) {
                if ($this->isContainer($this->_tree[$parent]) &&
                    !$this->isNamespace($this->_tree[$parent])) {
                    $this->delete($parent);
                } else {
                    $this->_modifyExpandedList($parent, 'remove');
                    $this->_setOpen($this->_tree[$parent], false);
                    $this->_addEltDiff($this->_tree[$parent], 'c');
                }
            }
        } else {
            /* Rebuild the parent tree. */
            $this->_parent[$parent] = array_values($this->_parent[$parent]);

            if (!$this->hasChildren($this->_tree[$parent])) {
                $this->_addEltDiff($this->_tree[$parent], 'c');
            }
        }

        /* Remove the mailbox from the expanded folders list. */
        $this->_modifyExpandedList($id, 'remove');

        /* Remove the mailbox from the nav_poll list. */
        $this->removePollList($id);

        return true;
    }

    /**
     * Subscribe an element to the tree.
     *
     * @param mixed $id  The element name or an array of element names.
     */
    public function subscribe($id)
    {
        if (!is_array($id)) {
            $id = array($id);
        }

        foreach ($id as $val) {
            $val = $this->_convertName($val);
            if (isset($this->_tree[$val])) {
                $this->_changed = true;
                $this->_setSubscribed($this->_tree[$val], true);
                $this->_setContainer($this->_tree[$val], false);
            }
        }
    }

    /**
     * Unsubscribe an element from the tree.
     *
     * @param mixed $id  The element name or an array of element names.
     */
    public function unsubscribe($id)
    {
        if (!is_array($id)) {
            $id = array($id);
        } else {
            /* We want to delete from the TOP of the tree down to ensure that
             * parents have an accurate view of what children are left. */
            $this->_sortList($id);
            $id = array_reverse($id);
        }

        foreach ($id as $val) {
            $val = $this->_convertName($val);

            /* INBOX can never be unsubscribed to. */
            if (isset($this->_tree[$val]) && ($val != 'INBOX')) {
                $this->_changed = true;

                $elt = &$this->_tree[$val];

                /* Do not delete from tree if there are child elements -
                 * instead, convert to a container element. */
                if (!$this->_showunsub && $this->hasChildren($elt)) {
                    $this->_setContainer($elt, true);
                }

                /* Set as unsubscribed, add to unsubscribed list, and remove
                 * from subscribed list. */
                $this->_setSubscribed($elt, false);
            }
        }
    }

    /**
     * Set an attribute for an element.
     *
     * @param array &$elt     The tree element.
     * @param integer $const  The constant to set/remove from the bitmask.
     * @param boolean $bool   Should the attribute be set?
     */
    protected function _setAttribute(&$elt, $const, $bool)
    {
        if ($bool) {
            $elt['a'] |= $const;
        } else {
            $elt['a'] &= ~$const;
        }
    }

    /**
     * Does the element have any active children?
     *
     * @param mixed $in        A mailbox name or a tree element.
     * @param boolean $filter  If true, honors the current iterator filter
     *                         settings when determining if active children
     *                         exist.
     *
     * @return boolean  True if the element has active children.
     */
    public function hasChildren($in, $filter = false)
    {
        $elt = $this->getElement($in);

        if ($elt && isset($this->_parent[$elt['v']])) {
            foreach ($this->_parent[$elt['v']] as $val) {
                if (($this->_showunsub &&
                     !$this->isContainer($this->_tree[$val]) &&
                     !$this->isNamespace($this->_tree[$val])) ||
                    $this->isSubscribed($this->_tree[$val]) ||
                    $this->hasChildren($this->_tree[$val])) {
                    /* If skipping special mailboxes, need to check an element
                     * for at least one non-special children. */
                    if (!$filter ||
                        !($this->_cache['filter']['mask'] & self::FLIST_NOSPECIALMBOXES) ||
                        !IMP_Mailbox::get($val)->special) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Is the tree element open?
     *
     * @param mixed $in  A mailbox name or a tree element.
     *
     * @return integer  True if the element is open.
     */
    public function isOpen($in)
    {
        $elt = $this->getElement($in);

        return ($elt &&
                ($elt['a'] & self::ELT_IS_OPEN) &&
                $this->hasChildren($elt, true));
    }

    /**
     * Set the open attribute for an element.
     *
     * @param array &$elt    A tree element.
     * @param boolean $bool  The setting.
     */
    protected function _setOpen(&$elt, $bool)
    {
        $this->_setAttribute($elt, self::ELT_IS_OPEN, $bool);
        $this->_modifyExpandedList($elt['v'], $bool ? 'add' : 'remove');
    }

    /**
     * Is this element a container only, not a mailbox (meaning you can
     * not open it)?
     *
     * @param mixed $in  A mailbox name or a tree element.
     *
     * @return integer  True if the element is a container.
     */
    public function isContainer($in)
    {
        $elt = $this->getElement($in);

        return ($elt &&
                (($elt['a'] & self::ELT_NOSELECT) ||
                 (!$this->_showunsub &&
                  !$this->isSubscribed($elt) &&
                  $this->hasChildren($elt, true))));
    }

    /**
     * Set the element as a container?
     *
     * @param array &$elt    A tree element.
     * @param boolean $bool  Is the element a container?
     */
    protected function _setContainer(&$elt, $bool)
    {
        $this->_setAttribute($elt, self::ELT_NOSELECT, $bool);
        $this->_addEltDiff($elt, 'c');
    }

    /**
     * Is the user subscribed to this element?
     *
     * @param mixed $in  A mailbox name or a tree element.
     *
     * @return integer  True if the user is subscribed to the element.
     */
    public function isSubscribed($in)
    {
        $elt = $this->getElement($in);

        return ($elt && ($elt['a'] & self::ELT_IS_SUBSCRIBED));
    }

    /**
     * Set the subscription status for an element.
     *
     * @param array &$elt    A tree element.
     * @param boolean $bool  Is the element subscribed to?
     */
    protected function _setSubscribed(&$elt, $bool)
    {
        $this->_setAttribute($elt, self::ELT_IS_SUBSCRIBED, $bool);
        if (isset($this->_cache['subscribed'])) {
            if ($bool) {
                $this->_cache['subscribed'][$elt['v']] = 1;
            } else {
                unset($this->_cache['subscribed'][$elt['v']]);
            }
        }
    }

    /**
     * Is the element a namespace container?
     *
     * @param mixed $in  A mailbox name or a tree element.
     *
     * @return integer  True if the element is a namespace container.
     */
    public function isNamespace($in)
    {
        $elt = $this->getElement($in);

        return ($elt && ($elt['a'] & self::ELT_NAMESPACE));
    }

    /**
     * Is the element a non-IMAP element?
     *
     * @param mixed $in  A mailbox name or a tree element.
     *
     * @return integer  True if the element is a non-IMAP element.
     */
    public function isNonImapElt($in)
    {
        $elt = $this->getElement($in);

        return ($elt && ($elt['a'] & self::ELT_NONIMAP));
    }

    /**
     * Initialize the expanded folder list.
     */
    protected function _initExpandedList()
    {
        if (!isset($this->_cache['expanded'])) {
            $serialized = $GLOBALS['prefs']->getValue('expanded_folders');
            $this->_cache['expanded'] = $serialized
                ? unserialize($serialized)
                : array();
        }
    }

    /**
     * Add/remove an element to the expanded list.
     *
     * @param string $id      The element name to add/remove.
     * @param string $action  Either 'add' or 'remove';
     */
    protected function _modifyExpandedList($id, $action)
    {
        $this->_initExpandedList();

        if ($action == 'add') {
            $change = empty($this->_cache['expanded'][$id]);
            $this->_cache['expanded'][$id] = true;
        } else {
            $change = !empty($this->_cache['expanded'][$id]);
            unset($this->_cache['expanded'][$id]);
        }

        if ($change) {
            $GLOBALS['prefs']->setValue('expanded_folders', serialize($this->_cache['expanded']));
        }
    }

    /**
     * Initializes and returns the list of mailboxes to poll.
     *
     * @param boolean $sort  Sort the directory list?
     *
     * @return array  The list of mailboxes to poll (IMP_Mailbox objects).
     */
    public function getPollList($sort = false)
    {
        $this->setIteratorFilter(self::FLIST_NOCONTAINER);

        if ($GLOBALS['prefs']->getValue('nav_poll_all')) {
            return iterator_to_array($this);
        }

        $plist = array();
        foreach ($this as $val) {
            if ($this->isPolled($val)) {
                $plist[] = $val;
            }
        }

        if ($sort) {
            $ns_new = $this->_getNamespace(null);
            Horde_Imap_Client_Sort::sortMailboxes($plist, array(
                'delimiter' => $ns_new['delimiter'],
                'inbox' => true
            ));
        }

        return IMP_Mailbox::get(array_filter($plist));
    }

    /**
     * Init the poll list.
     */
    protected function _initPollList()
    {
        if (!isset($this->_cache['poll']) &&
            !$GLOBALS['prefs']->getValue('nav_poll_all')) {
            /* We ALWAYS poll the INBOX. */
            $this->_cache['poll'] = array('INBOX' => 1);

            /* Add the list of polled mailboxes from the prefs. */
            if ($navPollList = @unserialize($GLOBALS['prefs']->getValue('nav_poll'))) {
                $this->_cache['poll'] += $navPollList;
            }
        }
    }

    /**
     * Add element to the poll list.
     *
     * @param mixed $id  The element name or a list of element names to add.
     */
    public function addPollList($id)
    {
        if (!is_array($id)) {
            $id = array($id);
        }

        if (empty($id) ||
            $GLOBALS['prefs']->getValue('nav_poll_all') ||
            $GLOBALS['prefs']->isLocked('nav_poll')) {
            return;
        }

        $changed = false;

        $this->_initPollList();

        foreach (IMP_Mailbox::get($id) as $val) {
            if ($val->nonimap || $this->isContainer($val)) {
                continue;
            }

            $mbox_str = strval($val);

            if (!$this->isSubscribed($this->_tree[$mbox_str])) {
                $val->subscribe(true);
            }
            $this->_setPolled($this->_tree[$mbox_str], true);
            if (empty($this->_cache['poll'][$mbox_str])) {
                $this->_cache['poll'][$mbox_str] = true;
                $changed = true;
            }
        }

        if ($changed) {
            $this->_updatePollList();
        }
    }

    /**
     * Remove element from the poll list.
     *
     * @param mixed $id  The mailbox (or a list of mailboxes) to remove.
     */
    public function removePollList($id)
    {
        if ($GLOBALS['prefs']->getValue('nav_poll_all') ||
            $GLOBALS['prefs']->isLocked('nav_poll')) {
            return;
        }

        if (!is_array($id)) {
            $id = array($id);
        }

        $removed = false;

        $this->_initPollList();

        foreach (IMP_Mailbox::get($id) as $val) {
            if (!$val->inbox &&
                isset($this->_cache['poll'][strval($val)])) {
                unset($this->_cache['poll'][strval($val)]);
                if (isset($this->_tree[strval($val)])) {
                    $this->_setPolled($this->_tree[strval($val)], false);
                }
                $removed = true;
            }
        }

        if ($removed) {
            $this->_updatePollList();
        }
    }

    /**
     * Update the nav_poll preference.
     */
    protected function _updatePollList()
    {
        $GLOBALS['prefs']->setValue('nav_poll', serialize($this->_cache['poll']));
        $this->_changed = true;
    }

    /**
     * Prune non-existent mailboxes from poll list.
     */
    public function prunePollList()
    {
        $prune = array();

        $this->setIteratorFilter(self::FLIST_NOCONTAINER);
        foreach ($this as $val) {
            if (!$this->isPolled($val) || !$val->exists) {
                $prune[] = $val;
            }
        }

        $this->removePollList($prune);
    }

    /**
     * Does the user want to poll this mailbox for new/unseen messages?
     *
     * @param mixed $in  A mailbox name or a tree element.
     *
     * @return integer  True if the user wants to poll the element.
     */
    public function isPolled($in)
    {
        $elt = $this->getElement($in);

        if ($this->isNonImapElt($in) || $this->isContainer($in)) {
            return false;
        }

        return $GLOBALS['prefs']->getValue('nav_poll_all')
            ? true
            : ($elt && ($elt['a'] & self::ELT_IS_POLLED));
    }

    /**
     * Set the polled attribute for an element.
     *
     * @param array &$elt    A tree element.
     * @param boolean $bool  The setting.
     */
    protected function _setPolled(&$elt, $bool)
    {
        $this->_setAttribute($elt, self::ELT_IS_POLLED, $bool);
    }

    /**
     * Is the element invisible?
     *
     * @param mixed $in  A mailbox name or a tree element.
     *
     * @return integer  True if the element is marked as invisible.
     */
    public function isInvisible($in)
    {
        $elt = $this->getElement($in);

        return ($elt && ($elt['a'] & self::ELT_INVISIBLE));
    }

    /**
     * Set the invisible attribute for an element.
     *
     * @param array &$elt    A tree element.
     * @param boolean $bool  The setting.
     */
    protected function _setInvisible(&$elt, $bool)
    {
        $this->_setAttribute($elt, self::ELT_INVISIBLE, $bool);
    }

    /**
     * Flag the element as needing its children to be sorted.
     *
     * @param array &$elt    A tree element.
     * @param boolean $bool  The setting.
     */
    protected function _setNeedSort(&$elt, $bool)
    {
        $this->_setAttribute($elt, self::ELT_NEED_SORT, $bool);
    }

    /**
     * Does this element's children need sorting?
     *
     * @param array $elt  A tree element.
     *
     * @return integer  True if the children need to be sorted.
     */
    protected function _needSort($elt)
    {
        return (($elt['a'] & self::ELT_NEED_SORT) && (count($this->_parent[$elt['v']]) > 1));
    }

    /**
     * Should we expand all elements?
     */
    public function expandAll()
    {
        foreach ($this->_parent[self::BASE_ELT] as $val) {
            $this->expand($val, true);
        }
    }

    /**
     * Should we collapse all elements?
     */
    public function collapseAll()
    {
        foreach ($this->_tree as $key => $val) {
            if ($key !== self::BASE_ELT) {
                $this->collapse($val['v']);
            }
        }
    }

    /**
     * Switch subscribed/unsubscribed viewing.
     *
     * @param boolean $unsub  Show unsubscribed elements?
     */
    public function showUnsubscribed($unsub)
    {
        if ((bool)$unsub === $this->_showunsub) {
            return;
        }

        $this->_showunsub = $unsub;
        $this->_changed = true;

        /* If we are switching from unsubscribed to subscribed, no need
         * to do anything (we just ignore unsubscribed stuff). */
        if ($unsub === false) {
            return;
        }

        /* If we are switching from subscribed to unsubscribed, we need
         * to add all unsubscribed elements that live in currently
         * discovered items. */
        $old_track = $this->track;
        $this->track = false;
        $this->_insert($this->_getList(true), false);
        $this->track = $old_track;
    }

    /**
     * Sorts a list of mailboxes.
     *
     * @param array &$mbox   The list of mailboxes to sort.
     * @param boolean $base  Are we sorting a list of mailboxes in the base
     *                       of the tree.
     */
    protected function _sortList(&$mbox, $base = false)
    {
        if (count($mbox) < 2) {
            return;
        }

        if (!$base) {
            Horde_Imap_Client_Sort::sortMailboxes($mbox, array('delimiter' => $this->_delimiter));
            return;
        }

        $basesort = $othersort = array();
        /* INBOX always appears first. */
        $sorted = array('INBOX');

        foreach ($mbox as $key => $val) {
            if ($this->isNonImapElt($this->_tree[$val])) {
                $othersort[$key] = IMP_Mailbox::get($val)->label;
            } elseif ($val !== 'INBOX') {
                $basesort[$key] = IMP_Mailbox::get($val)->label;
            }
        }

        natcasesort($basesort);
        natcasesort($othersort);
        foreach (array_merge(array_keys($basesort), array_keys($othersort)) as $key) {
            $sorted[] = $mbox[$key];
        }

        $mbox = $sorted;
    }

    /**
     * Convert a mailbox name to the correct, internal name (i.e. make sure
     * INBOX is always capitalized for IMAP servers).
     *
     * @param string $name  The mailbox name.
     *
     * @return string  The converted name.
     */
    protected function _convertName($name)
    {
        return (strcasecmp($name, 'INBOX') == 0)
            ? 'INBOX'
            : strval($name);
    }

    /**
     * Get namespace info for a full mailbox path.
     *
     * @param string $mailbox  The mailbox path.
     *
     * @return mixed  The namespace info for the mailbox path, or null if the
     *                path doesn't exist.
     */
    protected function _getNamespace($mailbox)
    {
        if (!in_array($mailbox, array(self::OTHER_KEY, self::SHARED_KEY, self::VFOLDER_KEY)) &&
            (strpos($mailbox, self::VFOLDER_KEY . $this->_delimiter) !== 0)) {
            return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->getNamespace($mailbox);
        }
        return null;
    }

    /**
     * Explicitly mark an element as added.
     *
     * @param mixed $in  A mailbox name or a tree element.
     */
    public function addEltDiff($elt)
    {
        $this->_addEltDiff($this->getElement($elt), 'a');
    }

    /**
     * Mark an element as changed.
     *
     * @param array $elt    An element array.
     * @param string $type  Either 'a', 'c', or 'd'.
     */
    protected function _addEltDiff($elt, $type)
    {
        if (!$this->track) {
            return;
        }

        $ed = &$this->_eltdiff;
        $id = $elt['v'];

        if (array_key_exists($id, $ed['o'])) {
            if (($type != 'd') && ($ed['o'][$id] == $elt)) {
                unset(
                    $ed['a'][$id],
                    $ed['c'][$id],
                    $ed['d'][$id],
                    $ed['o'][$id]
                );
                return;
            }
        } else {
            $ed['o'][$id] = ($type == 'a')
                ? null
                : $elt;
        }

        switch ($type) {
        case 'a':
            unset($ed['c'][$id], $ed['d'][$id]);
            $ed['a'][$id] = 1;
            break;

        case 'c':
            if (!isset($ed['a'][$id])) {
                $ed['c'][$id] = 1;
            }
            break;

        case 'd':
            unset($ed['a'][$id], $ed['c'][$id]);
            $ed['d'][$id] = 1;
            break;
        }
    }

    /**
     * Reset eltdiff array.
     */
    protected function _resetEltDiff()
    {
        $this->_eltdiff = array(
            'a' => array(),
            'c' => array(),
            'd' => array(),
            'o' => array()
        );
    }

    /**
     * Returns whether this element is a virtual folder.
     *
     * @param mixed $in  A mailbox name or a tree element.
     *
     * @return integer  True if the element is a virtual folder.
     */
    public function isVFolder($in)
    {
        $elt = $this->getElement($in);

        return ($elt && ($elt['a'] & self::ELT_VFOLDER));
    }

    /**
     * Rename a mailbox.
     *
     * @param string $old  The old mailbox name.
     * @param string $new  The new mailbox name.
     */
    public function rename($old, $new)
    {
        $new_list = $polled = array();

        $this->setIteratorFilter(self::FLIST_NOCONTAINER | self::FLIST_UNSUB | self::FLIST_NOBASE | self::FLIST_ASIS, $old);
        $old_list = array_merge(
            array(IMP_Mailbox::get($old)),
            iterator_to_array($this)
        );

        foreach ($old_list as $val) {
            $new_list[] = $new_name = substr_replace($val, $new, 0, strlen($old));
            if ($val->polled) {
                $polled[] = $new_name;
            }
        }

        $this->insert($new_list);
        $this->delete($old_list);

        $this->addPollList($polled);
    }

    /**
     * Sort a level in the tree.
     *
     * @param string $id  The parent mailbox whose children need to be sorted.
     */
    protected function _sortLevel($id)
    {
        if ($this->_needSort($this->_tree[$id])) {
            $this->_sortList($this->_parent[$id], ($id === self::BASE_ELT));
            $this->_setNeedSort($this->_tree[$id], false);
            $this->_changed = true;
        }
    }

    /**
     * Determines the mailbox name to create given a parent and the new name.
     *
     * @param string $parent  The parent name (UTF-8).
     * @param string $parent  The new mailbox name (UTF-8).
     *
     * @return IMP_Mailbox  The new mailbox.
     * @throws IMP_Exception
     */
    public function createMailboxName($parent, $new)
    {
        $ns_info = empty($parent)
            ? $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->defaultNamespace()
            : $this->_getNamespace($parent);

        if (is_null($ns_info)) {
            if ($this->isNamespace($this->_tree[$parent])) {
                $ns_info = $this->_getNamespace($new);
                if (in_array($ns_info['type'], array(Horde_Imap_Client::NS_OTHER, Horde_Imap_Client::NS_SHARED))) {
                    return IMP_Mailbox::get($new);
                }
            }
            throw new IMP_Exception(_("Cannot directly create mailbox in this folder."));
        }

        $mbox = $ns_info['name'];
        if (!empty($parent)) {
            $mbox .= substr_replace($parent, '', 0, strlen($ns_info['name']));
            $mbox = rtrim($mbox, $ns_info['delimiter']) . $ns_info['delimiter'];
        }

        return IMP_Mailbox::get($mbox . $new);
    }

    /**
     * Creates a Horde_Tree representation of the current tree (respecting
     * the current iterator filter).
     *
     * @param string|Horde_Tree $name  Either the tree name, or a Horde_Tree
     *                                 object to add nodes to.
     * @param array $opts              Additional options:
     *   - basename: (boolean) Use raw basename instead of abbreviated label?
     *               DEFAULT: false
     *   - checkbox: (boolean) Display checkboxes?
     *               DEFAULT: false
     *   - editvfolder: (boolean) Display vfolder edit links?
     *                  DEFAULT: false
     *   - open: (boolean) Force child mailboxes to this status.
     *           DEFAULT: null
     *   - parent: (string) The parent object of the current level.
     *             DEFAULT: null (add to base level)
     *   - poll_info: (boolean) Include poll information?
     *                DEFAULT: false
     *   - render_params: (array) List of params to pass to renderer if
     *                    auto-creating.
     *                    DEFAULT: 'alternate', 'lines', and 'lines_base'
     *                             are passed in with true values.
     *   - render_type: (string) The renderer name.
     *                  DEFAULT: Javascript
     *
     * @return Horde_Tree  The tree object.
     */
    public function createTree($name, array $opts = array())
    {
        global $injector, $registry;

        $opts = array_merge(array(
            'parent' => null,
            'render_params' => array(),
            'render_type' => 'Javascript'
        ), $opts);

        $this->_unseen = 0;
        $view = $registry->getView();

        if ($name instanceof Horde_Tree_Renderer_Base) {
            $tree = $name;
            $parent = $opts['parent'];
        } else {
            $tree = $injector->getInstance('Horde_Core_Factory_Tree')->create($name, $opts['render_type'], array_merge(array(
                'alternate' => true,
                'lines' => true,
                'lines_base' => true,
                'nosession' => true
            ), $opts['render_params']));
            $parent = null;
        }

        foreach ($this as $val) {
            $after = '';
            $elt = $this->getElement($val);
            $params = array();

            switch ($opts['render_type']) {
            case 'IMP_Tree_Flist':
                if ($val->vfolder_container) {
                    continue 2;
                }

                $is_open = true;
                $label = $params['orig_label'] = empty($opts['basename'])
                    ? $val->abbrev_label
                    : $val->basename;
                break;

            case 'IMP_Tree_Jquerymobile':
                $is_open = true;
                $label = $val->display_html;
                $icon = $val->icon;
                $params['icon'] = $icon->icon;
                $params['special'] = $val->inbox || $val->special;
                $params['class'] = 'imp-folder';
                $params['urlattributes'] = array(
                    'id' => 'imp-mailbox-' . $val->form_to
                );

                /* Force to flat tree so that non-polled parents don't cause
                 * polled children to be skipped by renderer (see Bug
                 * #11238). */
                $elt['c'] = 0;
                break;

            case 'IMP_Tree_Simplehtml':
                $is_open = $this->isOpen($val);
                if ($tree->shouldToggle($val->form_to)) {
                    if ($is_open) {
                        $this->collapse($val);
                    } else {
                        $this->expand($val);
                    }
                    $is_open = !$is_open;
                }
                $label = htmlspecialchars(Horde_String::abbreviate($val->display, 30 - ($elt['c'] * 2)));
                break;

            case 'Javascript':
                $is_open = $this->isOpen($val);
                $label = empty($opts['basename'])
                    ? htmlspecialchars($val->abbrev_label)
                    : htmlspecialchars($val->basename);
                $icon = $val->icon;
                $params['icon'] = $icon->icon;
                $params['iconopen'] = $icon->iconopen;
                break;
            }

            if (!empty($opts['poll_info']) && $val->polled) {
                $poll_info = $val->poll_info;

                if ($poll_info->unseen) {
                    switch ($opts['render_type']) {
                    case 'IMP_Tree_Jquerymobile':
                        $after = $poll_info->unseen;
                        break;

                    default:
                        $this->_unseen += $poll_info->unseen;
                        $label = '<strong>' . $label . '</strong>&nbsp;(' .
                            $poll_info->unseen . ')';
                    }
                }
            }

            if ($this->isContainer($val)) {
                $params['container'] = true;
            } else {
                switch ($view) {
                case $registry::VIEW_MINIMAL:
                    $params['url'] = IMP_Minimal_Mailbox::url(array('mailbox' => $val));
                    break;

                case $registry::VIEW_SMARTMOBILE:
                    $url = new Horde_Core_Smartmobile_Url();
                    $url->add('mbox', $val->form_to);
                    $url->setAnchor('mailbox');
                    $params['url'] = strval($url);
                    break;

                default:
                    $params['url'] = $val->url('mailbox.php')->setRaw(true);
                    break;
                }

                if ($this->_showunsub && !$this->isSubscribed($val)) {
                    $params['class'] = 'mboxunsub';
                }
            }

            $checkbox = empty($opts['checkbox'])
                ? ''
                : '<input type="checkbox" class="checkbox" name="mbox_list[]" value="' . $val->form_to . '"';

            if ($val->vfolder) {
                $checkbox .= ' disabled="disabled"';

                if (!empty($opts['editvfolder']) && $this->isContainer($val)) {
                    $after = '&nbsp[' .
                        $registry->getServiceLink('prefs', 'imp')->add('group', 'searches')->link(array('title' => _("Edit Virtual Folder"))) . _("Edit") . '</a>'.
                        ']';
                }
            }

            $tree->addNode(array(
                'id' => $val->form_to,
                'parent' => ($elt['c']) ? $val->parent->form_to : $parent,
                'label' => $label,
                'expanded' => isset($opts['open']) ? $opts['open'] : $is_open,
                'params' => $params,
                'right' => $after,
                'left' => empty($opts['checkbox']) ? null : $checkbox . ' />'
            ));
        }

        return $tree;
    }

    /**
     * Returns the internal IMAP Tree element for a given mailbox.
     *
     * @param mixed $in  A mailbox name or a tree element.
     *
     * @return mixed  The element array, or null if not found.
     */
    public function getElement($in)
    {
        if (is_array($in)) {
            return $in;
        }

        $in = $this->_convertName($in);

        return isset($this->_tree[$in])
            ? $this->_tree[$in]
            : null;
    }

    /**
     * Prepares an AJAX Mailbox response.
     *
     * @return array  The object used by JS code to update the tree.
     */
    public function getAjaxResponse()
    {
        $changes = $this->_eltdiff;
        $this->_resetEltDiff();
        if ($changes != $this->_eltdiff) {
            $this->_changed = true;
        }

        $result = array();

        if (!empty($changes['a'])) {
            $result['a'] = array();
            foreach (array_keys($changes['a']) as $val) {
                $result['a'][] = $this->_ajaxElt($val);
            }
        }

        if (!empty($changes['c'])) {
            $result['c'] = array();
            foreach (array_keys($changes['c']) as $val) {
                // Skip the base element, since any change there won't ever be
                // updated on-screen.
                if ($val != self::BASE_ELT) {
                    $result['c'][] = $this->_ajaxElt($val);
                }
            }
        }

        if (!empty($changes['d'])) {
            $result['d'] = array();
            foreach (array_reverse(array_keys($changes['d'])) as $val) {
                $result['d'][] = IMP_Mailbox::get($val)->form_to;
            }
        }

        return $result;
    }

    /**
     * Create an object sent in an AJAX response.
     *
     * @param mixed $elt  A mailbox object/string.
     *
     * @return stdClass  The element object. Contains the following items:
     *   - ch: (boolean) [children] Does the mailbox contain children?
     *         DEFAULT: no
     *   - cl: (string) [class] The CSS class.
     *         DEFAULT: 'base'
     *   - co: (boolean) [container] Is this mailbox a container element?
     *         DEFAULT: no
     *   - i: (string) [icon] A user defined icon to use.
     *        DEFAULT: none
     *   - l: (string) [label] The mailbox display label.
     *        DEFAULT: 'm' val
     *   - m: (string) [mbox] The mailbox value (base64url encoded).
     *   - n: (boolean) [non-imap] A non-IMAP element?
     *        DEFAULT: no
     *   - pa: (string) [parent] The parent element.
     *         DEFAULT: DimpCore.conf.base_mbox
     *   - po: (boolean) [polled] Is the element polled?
     *         DEFAULT: no
     *   - s: (boolean) [special] Is this a "special" element?
     *        DEFAULT: no
     *   - t: (string) [title] Mailbox title.
     *        DEFAULT: 'm' val
     *   - un: (boolean) [unsubscribed] Is this mailbox unsubscribed?
     *         DEFAULT: no
     *   - v: (integer) [virtual] Virtual folder? 0 = not vfolder, 1 = system
     *        vfolder, 2 = user vfolder
     *        DEFAULT: 0
     */
    protected function _ajaxElt($elt)
    {
        if (!is_object($elt)) {
            $elt = $this[$elt];
        }

        $ob = new stdClass;

        if ($this->hasChildren($elt, true)) {
            $ob->ch = 1;
        }
        $ob->m = $elt->form_to;

        $label = $elt->label;
        if ($ob->m != $label) {
            $ob->t = $label;
        }

        $tmp = htmlspecialchars($elt->abbrev_label);
        if ($ob->m != $tmp) {
            $ob->l = $tmp;
        }

        $parent = $elt->parent;
        if ($parent != self::BASE_ELT) {
            $ob->pa = $parent->form_to;
        }
        if ($elt->vfolder) {
            $ob->v = $elt->editvfolder ? 2 : 1;
        }
        if (!$this->isSubscribed($elt)) {
            $ob->un = 1;
        }

        if ($this->isContainer($elt)) {
            $ob->cl = 'exp';
            $ob->co = 1;
            if ($elt->nonimap) {
                $ob->n = 1;
            }
            if ($elt == self::VFOLDER_KEY) {
                $ob->v = 1;
            }
        } else {
            if ($elt->polled) {
                $ob->po = 1;
            }

            if ($elt->inbox || $elt->special) {
                $ob->s = 1;
            } elseif (empty($ob->v) && $this->hasChildren($elt, true)) {
                $ob->cl = 'exp';
            }
        }

        $icon = $elt->icon;
        if ($icon->user_icon) {
            $ob->cl = 'customimg';
            $ob->i = strval($icon->icon);
        } else {
            $ob->cl = $icon->class;
        }

        return $ob;
    }

    /* ArrayAccess methods. */

    public function offsetExists($offset)
    {
        return isset($this->_tree[$this->_convertName($offset)]);
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset)
            ? IMP_Mailbox::get($this->_convertName($offset))
            : null;
    }

    public function offsetSet($offset, $value)
    {
        $this->insert($offset);
    }

    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    /* Countable methods. */

    /**
     * Return the number of mailboxes on the server.
     */
    public function count()
    {
        $this->setIteratorFilter(self::FLIST_NOCONTAINER | self::FLIST_UNSUB);
        return count(iterator_to_array($this));
    }

    /* Iterator methods. */

    public function current()
    {
        return $this->valid()
            ? $this[$this->_parent[$this->_currparent][$this->_currkey]]
            : null;
    }

    public function key()
    {
        return $this->valid()
            ? $this->_parent[$this->_currparent][$this->_currkey]
            : null;
    }

    public function next()
    {
        $curr = $this->current();
        if (is_null($curr)) {
            return;
        }

        $c = &$this->_cache['filter'];

        $old_showunsub = $this->_showunsub;
        if ($c['mask'] & self::FLIST_UNSUB) {
            $this->_showunsub = true;
        }

        if ($this->_activeElt($curr, true)) {
            /* Move into child element. */
            $this->_currkey = 0;
            $this->_currparent = $curr->value;
            $this->_sortLevel($curr->value);

            $curr = $this->current();
        } else {
            /* Else, increment within the current subfolder. */
            ++$this->_currkey;

            /* Descend tree until we reach a level that has more leaves we
             * have not yet traversed. */
            while ((($curr = $this->current()) === null) &&
                   (!isset($c['samelevel']) ||
                    ($c['samelevel'] != $this->_currparent)) &&
                   ($parent = $this->_getParent($this->_currparent, true))) {
                list($this->_currparent, $this->_currkey) = $parent;
            }
        }

        if (is_null($curr)) {
            /* If we don't have a current element by this point, we have run
             * off the end of the tree. */
            $this->_currkey = null;
        } elseif (!$this->_activeElt($curr)) {
            $this->next();
        }

        $this->_showunsub = $old_showunsub;
    }

    public function rewind()
    {
        $this->_currkey = 0;
        $this->_currparent = self::BASE_ELT;
        $this->_sortLevel($this->_currparent);

        $c = &$this->_cache['filter'];

        /* If showing unsubscribed, toggle subscribed flag to make sure we
         * have subscribed mailbox information. */
        if (!$this->_showunsub &&
            ($c['mask'] & self::FLIST_UNSUB) &&
            !($c['mask'] & self::FLIST_ASIS)) {
            $this->showUnsubscribed(true);
            $this->showUnsubscribed(false);
        }

        // Search for base element.
        if ($c['base']) {
            if ($tmp = $this[$c['base']]) {
                if ($c['mask'] & self::FLIST_ANCESTORS) {
                    $p = $tmp->value;
                    $c['ancestors'] = array($p => null);
                    while ($parent = $this->_getParent($p)) {
                        $c['ancestors'][$parent[0]] = $parent[1];
                        $p = $parent[0];
                    }
                } elseif ($c['mask'] & self::FLIST_NOBASE) {
                    $this->_currparent = $tmp->value;
                    $this->_currkey = isset($this->_parent[$tmp->value])
                        ? 0
                        : null;
                    $c['samelevel'] = $tmp->value;

                    /* Check to make sure current element is valid. */
                    $curr = $this->current();
                    if (is_null($curr)) {
                        $this->_currkey = null;
                    } elseif (!$this->_activeElt($curr)) {
                        $this->next();
                    }
                } else {
                    $this->_currparent = strval($tmp->parent);
                    $this->_currkey = array_search($tmp->value, $this->_parent[$this->_currparent]);

                    if ($c['mask'] & self::FLIST_SAMELEVEL) {
                        $this->_currkey = 0;
                        $c['samelevel'] = $this->_currparent;
                    }
                }
            } else {
                $this->_currkey = null;
            }
        }
    }

    public function valid()
    {
        return (!is_null($this->_currkey) &&
                isset($this->_parent[$this->_currparent][$this->_currkey]));
    }

    /* Helper functions for Iterator methods. */

    /**
     * Set the current iterator filter and reset the internal pointer.
     *
     * This filter is "sticky" - it will remain set until setIteratorFilter()
     * is called with new arguments.
     *
     * @param integer $mask  A mask with the following possible elements:
     * <ul>
     *  <li>
     *   IMP_Imap_Tree::FLIST_NOCONTAINER: Don't include container elements.
     *   Default: Container elements are included.
     *  </li>
     *  <li>
     *   IMP_Imap_Tree::FLIST_UNSUB: Include unsubscribed elements.
     *   Default: Unsubcribed elements are not included.
     *  </li>
     *  <li>
     *   IMP_Imap_Tree::FLIST_VFOLDER: Include Virtual Folders.
     *   Default: Virtual folders are not included.
     *  </li>
     *  <li>
     *   IMP_Imap_Tree::FLIST_NOCHILDREN: Don't include child elements.
     *   Default: Child elements are included
     *  </li>
     *  <li>
     *   IMP_Imap_Tree::FLIST_EXPANDED: Only include expanded mailboxes.
     *   Default: Expanded status is ignored.
     *  </li>
     *  <li>
     *   IMP_Imap_Tree::FLIST_ASIS: Display the list as is currently cached
     *   in this object.
     *   Default: Object may be updated as needed.
     *  </li>
     *  <li>
     *   IMP_Imap_Tree::FLIST_NOSPECIALMBOXES: Don't display special mailboxes.
     *   Default: Special mailboxes are displayed.
     *  </li>
     *  <li>
     *   IMP_Imap_Tree::FLIST_POLLED: Only show polled mailboxes.
     *   Default: Polled status is ignored.
     *  </li>
     *  <li>Options that require $base to be set:
     *   <ul>
     *    <li>
     *     IMP_Imap_Tree::FLIST_ANCESTORS: Include ancestors of $base.
     *    </li>
     *    <li>
     *     IMP_Imap_Tree::FLIST_SAMELEVEL: Include all mailboxes at the same
     *                                     level as $base.
     *    </li>
     *    <li>
     *     IMP_Imap_Tree::FLIST_NOBASE: Don't include $base in the return.
     *    </li>
     *   </ul>
     *  </li>
     * </ul>
     * @param string $base  Include all mailboxes below this element.
     */
    public function setIteratorFilter($mask = 0, $base = null)
    {
        $this->_cache['filter'] = array(
            'base' => $base,
            'mask' => $mask
        );
        reset($this);
    }

    /**
     * Is the given element an "active" element (i.e. an element that should
     * be worked with given the current viewing parameters).
     *
     * @param IMP_Mailbox $elt      A mailbox element.
     * @param boolean $child_check  Check children?
     *
     * @return boolean  True if it is an active element.
     */
    protected function _activeElt($elt, $child_check = false)
    {
        /* Skip invisible elements. */
        if ($this->isInvisible($elt)) {
            return false;
        }

        $c = &$this->_cache['filter'];

        /* Skip virtual folders unless told to display them. */
        if (!($c['mask'] & self::FLIST_VFOLDER) && $elt->vfolder) {
            return false;
        }

        if ($child_check) {
            /* Checks done when determining whether to proceed into child
             * node. */

            if (!isset($this->_parent[$elt->value])) {
               return false;
            }

            /* If element exists in ancestors list, it is valid. */
            if (isset($c['ancestors']) &&
                isset($c['ancestors'][$this->_currparent]) &&
                ($c['ancestors'][$this->_currparent] == $this->_currkey)) {
                return true;
            }

            /* If expanded is requested, we assume it overrides nochildren. */
            if ($c['mask'] & self::FLIST_EXPANDED) {
                return $this->isOpen($elt);
            }

            /* Explicitly don't return child elements. */
            if ($c['mask'] & self::FLIST_NOCHILDREN) {
                return false;
            }
        } else {
            /* Checks done when determining whether to mark current element as
             * valid. */

            /* Show containers if NOCONTAINER is not set and children exist. */
            if ($this->isContainer($elt)) {
                if (($c['mask'] & self::FLIST_NOCONTAINER) ||
                    !$this->hasChildren($elt, true)) {
                    return false;
                }
            } elseif (!$this->_showunsub && !$this->isSubscribed($elt)) {
                /* Don't show element if not subscribed. */
                return false;
            } elseif ($elt->special) {
                /* Skip special mailboxes if requested. Otherwise, always
                 * show. */
                if ($c['mask'] & self::FLIST_NOSPECIALMBOXES) {
                    return false;
                }
            } elseif (($c['mask'] & self::FLIST_POLLED) && !$elt->polled) {
                /* Skip non-polled mailboxes if requested. */
                return false;
            }
        }

        return true;
    }

    /**
     * Return the parent/key for the current element.
     *
     * @param boolean $inc  Increment key?
     *
     * @return mixed  An array with two values: parent name and the key.
     *                Returns null if no parent.
     */
    protected function _getParent($parent, $inc = false)
    {
        if ($parent == self::BASE_ELT) {
            return null;
        }

        $p = strval($this[$parent]->parent);

        return array(
            $p,
            array_search($parent, $this->_parent[$p], true) + ($inc ? 1 : 0)
        );
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return serialize(array(
            // Serialized data ID.
            self::VERSION,
            $this->_delimiter,
            $this->track ? $this->_eltdiff : null,
            $this->_namespaces,
            $this->_parent,
            $this->_showunsub,
            $this->_tree
        ));
    }

    /**
     * @throws Exception
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data[0]) ||
            ($data[0] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->_delimiter = $data[1];
        $this->_eltdiff = is_null($data[2])
            ? $this->_resetEltDiff()
            : $data[2];
        $this->_namespaces = $data[3];
        $this->_parent = $data[4];
        $this->_showunsub = $data[5];
        $this->_tree = $data[6];
    }

}
