<?php
/**
 * The IMP_Imap_Tree class provides a tree view of the mailboxes in an
 * IMAP/POP3 repository.  It provides access functions to iterate through this
 * tree and query information about individual mailboxes.
 * In IMP, folders = IMAP mailboxes so the two terms are used interchangably.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @author   Anil Madhavapeddy <avsm@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Imap_Tree implements ArrayAccess, Iterator
{
    /* Constants for mailboxElt attributes. */
    const ELT_NOSELECT = 1;
    const ELT_NAMESPACE = 2;
    const ELT_IS_OPEN = 4;
    const ELT_IS_SUBSCRIBED = 8;
    const ELT_NOSHOW = 16;
    const ELT_IS_POLLED = 32;
    const ELT_NEED_SORT = 64;
    const ELT_VFOLDER = 128;
    const ELT_NONIMAP = 256;
    const ELT_INVISIBLE = 512;

    /* The isOpen() expanded mode constants. */
    const OPEN_NONE = 0;
    const OPEN_ALL = 1;
    const OPEN_USER = 2;

    /* The folder list filtering constants. */
    const FLIST_NOCONTAINER = 1;
    const FLIST_UNSUB = 2;
    const FLIST_VFOLDER = 4;
    const FLIST_NOCHILDREN = 8;
    const FLIST_EXPANDED = 16;
    const FLIST_ANCESTORS = 32;
    const FLIST_SAMELEVEL = 64;
    const FLIST_NOBASE = 128;

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
     * Tree changed flag.  Set when something in the tree has been altered.
     *
     * @var boolean
     */
    public $changed = false;

    /**
     * Recent messages.
     *
     * @var array
     */
    public $recent = array();

    /**
     * Unseen count.
     *
     * @var array
     */
    public $unseen = 0;

    /**
     * Array containing the mailbox tree.
     *
     * @var array
     */
    protected $_tree;

    /**
     * Location of current element in the tree.
     *
     * @var string
     */
    protected $_currparent = null;

    /**
     * Location of current element in the tree.
     *
     * @var integer
     */
    protected $_currkey = null;

    /**
     * Show unsubscribed mailboxes?
     *
     * @var boolean
     */
    protected $_showunsub = false;

    /**
     * Parent list.
     *
     * @var array
     */
    protected $_parent = array();

    /**
     * The string used for the IMAP delimiter.
     *
     * @var string
     */
    protected $_delimiter = '/';

    /**
     * The list of namespaces to add to the tree.
     *
     * @var array
     */
    protected $_namespaces = array();

    /**
     * Used to determine the list of element changes.
     *
     * @var array
     */
    protected $_eltdiff = null;

    /**
     * If set, track element changes.
     *
     * @var boolean
     */
    protected $_trackdiff = true;

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
     * Constructor.
     */
    public function __construct()
    {
        if ($_SESSION['imp']['protocol'] == 'imap') {
            $ns = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->getNamespaceList();
            $ptr = reset($ns);
            $this->_delimiter = $ptr['delimiter'];
            $this->_namespaces = empty($GLOBALS['conf']['user']['allow_folders'])
                ? array()
                : $ns;
        }

        $this->init();
    }

    /**
     * Do cleanup prior to serialization and provide a list of variables
     * to serialize.
     */
    public function __sleep()
    {
        return array(
            '_tree', '_showunsub', '_parent', '_delimiter', '_namespaces'
        );
    }

    /**
     * Initalize the tree.
     */
    public function init()
    {
        $unsubmode = (($_SESSION['imp']['protocol'] == 'pop') ||
                      !$GLOBALS['prefs']->getValue('subscribe') ||
                      $_SESSION['imp']['showunsub']);

        /* Reset class variables to the defaults. */
        $this->changed = true;
        $this->_currkey = $this->_currparent = null;
        $this->recent = $this->_tree = $this->_parent = array();
        $this->_showunsub = $unsubmode;
        unset($this->_cache['fulllist'], $this->_cache['subscribed']);

        /* Create a placeholder element to the base of the tree list so we can
         * keep track of whether the base level needs to be sorted. */
        $this->_tree[self::BASE_ELT] = array(
            'a' => self::ELT_NEED_SORT,
            'v' => self::BASE_ELT
        );

        /* Add INBOX and exit if folders aren't allowed or if we are using
         * POP3. */
        if (empty($GLOBALS['conf']['user']['allow_folders']) ||
            ($_SESSION['imp']['protocol'] == 'pop')) {
            $this->_insertElt($this->_makeElt('INBOX', self::ELT_IS_SUBSCRIBED));
            return;
        }

        /* Add namespace elements. */
        foreach ($this->_namespaces as $key => $val) {
            if ($val['type'] != 'personal' &&
                $GLOBALS['prefs']->getValue('tree_view')) {
                $elt = $this->_makeElt(
                    ($val['type'] == 'other') ? self::OTHER_KEY : self::SHARED_KEY,
                    self::ELT_NOSELECT | self::ELT_NAMESPACE | self::ELT_NONIMAP | self::ELT_NOSHOW
                );
                $elt['l'] = ($val['type'] == 'other')
                    ? _("Other Users' Folders")
                    : _("Shared Folders");

                foreach ($this->_namespaces as $val2) {
                    if (($val2['type'] == $val['type']) &&
                        ($val2['name'] != $val['name'])) {
                        $elt['a'] &= ~self::ELT_NOSHOW;
                        break;
                    }
                }

                $this->_insertElt($elt);
            }
        }

        /* Create the list (INBOX and all other hierarchies). */
        $this->_insert($this->_getList($this->_showunsub), $this->_showunsub ? null : true);

        /* Add virtual folders to the tree. */
        $this->insertVFolders($GLOBALS['injector']->getInstance('IMP_Search')->listQueries(IMP_Search::LIST_VFOLDER));
    }


    /**
     * Returns the list of mailboxes on the server.
     *
     * @param boolean $showunsub  Show unsubscribed mailboxes?
     *
     * @return array  See Horde_Imap_Client_Base::listMailboxes().
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

        try {
            $imp_imap = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb();
            $result = $imp_imap->listMailboxes($searches, $showunsub ? Horde_Imap_Client::MBOX_ALL : Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS, array('attributes' => true, 'delimiter' => true, 'sort' => true));

            /* INBOX must always appear. */
            if (empty($result['INBOX'])) {
                $result = $imp_imap->listMailboxes('INBOX', Horde_Imap_Client::MBOX_ALL, array('attributes' => true, 'delimiter' => true)) + $result;
            }
        } catch (Horde_Imap_Client_Exception $e) {
            $result = array();
        }

        $this->_cache[$showunsub ? 'fulllist' : 'subscribed'] = $result;

        return $result;
    }

    /**
     * Make a single mailbox tree element.
     * An element consists of the following items (we use single letters here
     * to save in session storage space):
     *   'a'  --  Attributes
     *   'c'  --  Level count
     *   'l'  --  Label
     *   'p'  --  Parent node
     *   'v'  --  Value
     *
     * @param string $name         The mailbox name.
     * @param integer $attributes  The mailbox's attributes.
     *
     * @return array  See above format.
     * @throws Horde_Exception
     */
    protected function _makeElt($name, $attributes = 0)
    {
        $elt = array(
            'a' => $attributes,
            'c' => 0,
            'p' => self::BASE_ELT,
            'v' => $name
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

        $ns_info = $this->_getNamespace($name);
        $delimiter = is_null($ns_info) ? $this->_delimiter : $ns_info['delimiter'];
        $tmp = explode($delimiter, $name);
        $elt['c'] = count($tmp) - 1;

        /* Get the mailbox label. */
        $label = IMP::getLabel($name);
        $elt['l'] = (($pos = strrpos($label, $delimiter)) === false)
            ? $label
            : substr($label, $pos + 1);

        if ($_SESSION['imp']['protocol'] != 'pop') {
            try {
                $this->_setInvisible($elt, !Horde::callHook('display_folder', array($elt['v']), 'imp'));
            } catch (Horde_Exception_HookNotSet $e) {}

            if ($elt['c'] != 0) {
                $elt['p'] = implode(is_null($ns_info) ? $this->_delimiter : $ns_info['delimiter'], array_slice($tmp, 0, $elt['c']));
            }

            if (!is_null($ns_info)) {
                switch ($ns_info['type']) {
                case 'personal':
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

                case 'other':
                case 'shared':
                    if (substr($ns_info['name'], 0, -1 * strlen($ns_info['delimiter'])) == $elt['v']) {
                        $elt['a'] = self::ELT_NOSELECT | self::ELT_NAMESPACE;
                    }

                    if ($GLOBALS['prefs']->getValue('tree_view')) {
                        $name = ($ns_info['type'] == 'other') ? self::OTHER_KEY : self::SHARED_KEY;
                        if ($elt['c'] == 0) {
                            $elt['p'] = $name;
                            ++$elt['c'];
                        } elseif ($this->_tree[$name] && self::ELT_NOSHOW) {
                            if ($elt['c'] == 1) {
                                $elt['p'] = $name;
                            }
                        } else {
                            ++$elt['c'];
                        }
                    }
                    break;
                }
            }
        }

        return $elt;
    }

    /**
     * Expand a mail folder.
     *
     * @param string $folder      The folder name to expand.
     * @param boolean $expandall  Expand all folders under this one?
     */
    public function expand($folder, $expandall = false)
    {
        $folder = $this->_convertName($folder);

        if (!isset($this->_tree[$folder])) {
            return;
        }
        $elt = &$this->_tree[$folder];

        if ($this->hasChildren($elt)) {
            if (!$this->isOpen($elt)) {
                $this->changed = true;
                $this->_setOpen($elt, true);
            }

            /* Expand all children beneath this one. */
            if ($expandall && !empty($this->_parent[$folder])) {
                foreach ($this->_parent[$folder] as $val) {
                    $this->expand($this->_tree[$val]['v'], true);
                }
            }
        }
    }

    /**
     * Collapse a mail folder.
     *
     * @param string $folder  The folder name to collapse.
     */
    public function collapse($folder)
    {
        $folder = $this->_convertName($folder);

        if (isset($this->_tree[$folder]) &&
            $this->isOpen($this->_tree[$folder])) {
            $this->changed = true;
            $this->_setOpen($this->_tree[$folder], false);
        }
    }

    /**
     * Insert a folder/mailbox into the tree.
     *
     * @param mixed $id  The name of the folder (or a list of folder names)
     *                   to add.
     */
    public function insert($id)
    {
        if (is_array($id)) {
            /* We want to add from the BASE of the tree up for efficiency
             * sake. */
            $this->_sortList($id);
        } else {
            $id = array($id);
        }

        /* Process Virtual Folders here. */
        reset($id);
        while (list($key, $val) = each($id)) {
            if (strpos($val, self::VFOLDER_KEY) === 0) {
                if (!isset($this->_tree[$val])) {
                    if (!isset($this->_tree[self::VFOLDER_KEY])) {
                        $elt = $this->_makeElt(self::VFOLDER_KEY, self::ELT_VFOLDER | self::ELT_NOSELECT | self::ELT_NONIMAP);
                        $elt['l'] = _("Virtual Folders");
                        $this->_insertElt($elt);
                    }

                    $elt = $this->_makeElt($val, self::ELT_VFOLDER | self::ELT_IS_SUBSCRIBED);
                    $elt['l'] = $elt['v'] = Horde_String::substr($val, Horde_String::length(self::VFOLDER_KEY) + Horde_String::length($this->_delimiter));
                    $this->_insertElt($elt);
                }

                unset($id[$key]);
            }
        }

        if (!empty($id)) {
            try {
                $this->_insert($GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->listMailboxes($id, Horde_Imap_Client::MBOX_ALL, array('attributes' => true, 'delimiter' => true, 'sort' => true)));
            } catch (Horde_Imap_Client_Exception $e) {}
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

        foreach ($elts as $key => $val) {
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

                    /* Set subscribed values. We know the folder is
                     * subscribed, without query of the IMAP server, in the
                     * following situations:
                     * + Subscriptions are turned off.
                     * + $sub is true.
                     * + Folder is INBOX.
                     * + Folder has the \subscribed attribute set. */
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

                    if (in_array('\noselect', $val['attributes'])) {
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
        if (!strlen($elt['l']) || isset($this->_tree[$elt['v']])) {
            return;
        }

        // UW fix - it may return both 'foo' and 'foo/' as folder names.
        // Only add one of these (without the namespace character) to
        // the tree.  See Ticket #5764.
        $ns_info = $this->_getNamespace($elt['v']);
        if (isset($this->_tree[rtrim($elt['v'], is_null($ns_info) ? $this->_delimiter : $ns_info['delimiter'])])) {
            return;
        }

        $this->changed = true;

        /* Set the parent array to the value in $elt['p']. */
        if (empty($this->_parent[$elt['p']])) {
            $this->_parent[$elt['p']] = array();
            // This is a case where it is possible that the parent element has
            // changed (it now has children) but we can't catch it via the
            // bitflag (since hasChildren() is dynamically determined).
            if ($this->_trackdiff &&
                !is_null($this->_eltdiff) &&
                !isset($this->_eltdiff['a'][$elt['p']])) {
                $this->_eltdiff['c'][$elt['p']] = 1;
            }
        }
        $this->_parent[$elt['p']][] = $elt['v'];
        $this->_tree[$elt['v']] = $elt;

        if ($this->_trackdiff && !is_null($this->_eltdiff)) {
            $this->_eltdiff['a'][$elt['v']] = 1;
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
        $search_id = $GLOBALS['injector']->getInstance('IMP_Search')->createSearchID($id);

        if ($vfolder_base ||
            (isset($this->_tree[$search_id]) &&
             $this->isVFolder($this->_tree[$search_id]))) {
            if (!$vfolder_base) {
                $id = $search_id;
            }

            $parent = $this->_tree[$id]['p'];
            unset($this->_tree[$id]);

            if (!is_null($this->_eltdiff)) {
                $this->_eltdiff['d'][$id] = 1;
            }

            /* Delete the entry from the parent tree. */
            $key = array_search($id, $this->_parent[$parent]);
            unset($this->_parent[$parent][$key]);

            /* Rebuild the parent tree. */
            if (!$vfolder_base && empty($this->_parent[$parent])) {
                $this->delete($parent);
            } else {
                $this->_parent[$parent] = array_values($this->_parent[$parent]);
            }
            $this->changed = true;

            return true;
        }

        $ns_info = $this->_getNamespace($id);

        if (($id == 'INBOX') ||
            !isset($this->_tree[$id]) ||
            ($id == $ns_info['name'])) {
            return false;
        }

        $this->changed = true;

        $elt = &$this->_tree[$id];

        /* Delete the entry from the folder list cache(s). */
        unset($this->_cache['fulllist'][$id], $this->_cache['subscribed'][$id]);

        /* Do not delete from tree if there are child elements - instead,
         * convert to a container element. */
        if ($this->hasChildren($elt)) {
            $this->_setContainer($elt, true);
            return true;
        }

        $parent = $elt['p'];

        /* Delete the tree entry. */
        unset($this->_tree[$id]);

        /* Delete the entry from the parent tree. */
        $key = array_search($id, $this->_parent[$parent]);
        unset($this->_parent[$parent][$key]);

        if (!is_null($this->_eltdiff)) {
            $this->_eltdiff['d'][$id] = 1;
        }

        if (empty($this->_parent[$parent])) {
            /* This folder is now completely empty (no children).  If the
             * folder is a container only, we should delete the folder from
             * the tree. */
            unset($this->_parent[$parent]);
            if (isset($this->_tree[$parent])) {
                if ($this->isContainer($this->_tree[$parent]) &&
                    !$this->isNamespace($this->_tree[$parent])) {
                    $this->delete($parent);
                } else {
                    $this->_modifyExpandedList($parent, 'remove');
                    $this->_setOpen($this->_tree[$parent], false);
                    /* This is a case where it is possible that the parent
                     * element has changed (it no longer has children) but
                     * we can't catch it via the bitflag (since hasChildren()
                     * is dynamically determined). */
                    if (!is_null($this->_eltdiff)) {
                        $this->_eltdiff['c'][$parent] = 1;
                    }
                }
            }
        } else {
            /* Rebuild the parent tree. */
            $this->_parent[$parent] = array_values($this->_parent[$parent]);
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
                $this->changed = true;
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
                $this->changed = true;

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
     * @param array $elt  A tree element.
     *
     * @return boolean  True if the element has active children.
     */
    public function hasChildren($elt)
    {
        if (isset($this->_parent[$elt['v']])) {
            if ($this->_showunsub) {
                return true;
            }

            foreach ($this->_parent[$elt['v']] as $val) {
                if ($this->isSubscribed($this->_tree[$val]) ||
                    $this->hasChildren($this->_tree[$val])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Is the tree element open?
     *
     * @param array $elt  A tree element.
     *
     * @return integer  True if the element is open.
     */
    public function isOpen($elt)
    {
        return (($elt['a'] & self::ELT_IS_OPEN) && $this->hasChildren($elt));
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
     * @param array $elt  A tree element.
     *
     * @return integer  True if the element is a container.
     */
    public function isContainer($elt)
    {
        return (($elt['a'] & self::ELT_NOSELECT) ||
                (!$this->_showunsub &&
                 !$this->isSubscribed($elt) &&
                 $this->hasChildren($elt)));
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
    }

    /**
     * Is the user subscribed to this element?
     *
     * @param array $elt  A tree element.
     *
     * @return integer  True if the user is subscribed to the element.
     */
    public function isSubscribed($elt)
    {
        return $elt['a'] & self::ELT_IS_SUBSCRIBED;
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
     * @param array $elt  A tree element.
     *
     * @return integer  True if the element is a namespace container.
     */
    public function isNamespace($elt)
    {
        return $elt['a'] & self::ELT_NAMESPACE;
    }

    /**
     * Is the element a non-IMAP element?
     *
     * @param array $elt  A tree element.
     *
     * @return integer  True if the element is a non-IMAP element.
     */
    public function isNonImapElt($elt)
    {
        return $elt['a'] & self::ELT_NONIMAP;
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
     * @param boolean $sort   Sort the directory list?
     * @param boolean $prune  Prune non-existent folders from list?
     *
     * @return array  The list of mailboxes to poll.
     */
    public function getPollList($sort = false, $prune = false)
    {
        $this->_initPollList();

        $plist = $prune
            ? array_values(array_intersect(array_keys($this->_cache['poll']), array_keys($this->_tree)))
            : array_keys($this->_cache['poll']);

        if ($sort) {
            $ns_new = $this->_getNamespace(null);
            Horde_Imap_Client_Sort::sortMailboxes($plist, array('delimiter' => $ns_new['delimiter'], 'inbox' => true));
        }

        return array_filter($plist);
    }

    /**
     * Init the poll list.  Called once per session.
     */
    protected function _initPollList()
    {
        if (!isset($this->_cache['poll'])) {
            /* We ALWAYS poll the INBOX. */
            $this->_cache['poll'] = array('INBOX' => 1);

            /* Add the list of polled mailboxes from the prefs. */
            if ($GLOBALS['prefs']->getValue('nav_poll_all')) {
                $navPollList = array_flip(array_keys($this->_getList(true)));
            } else {
                $navPollList = @unserialize($GLOBALS['prefs']->getValue('nav_poll'));
            }

            if ($navPollList) {
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

        if (empty($id) || $GLOBALS['prefs']->isLocked('nav_poll')) {
            return;
        }

        $changed = false;
        $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');

        $this->getPollList();
        foreach ($id as $val) {
            if (!$this->isSubscribed($this->_tree[$val])) {
                $imp_folder->subscribe(array($val));
            }
            $this->_setPolled($this->_tree[$val], true);
            if (empty($this->_cache['poll'][$val])) {
                $this->_cache['poll'][$val] = true;
                $changed = true;
            }
        }

        if ($changed) {
            $GLOBALS['prefs']->setValue('nav_poll', serialize($this->_cache['poll']));
            $GLOBALS['injector']->getInstance('IMP_Search')->createVINBOXFolder();
            $this->changed = true;
        }
    }

    /**
     * Remove element from the poll list.
     *
     * @param string $id  The folder/mailbox or a list of folders/mailboxes
     *                    to remove.
     */
    public function removePollList($id)
    {
        if ($GLOBALS['prefs']->isLocked('nav_poll')) {
            return;
        }

        if (!is_array($id)) {
            $id = array($id);
        }

        $removed = false;

        $this->getPollList();
        foreach ($id as $val) {
            if ($val != 'INBOX') {
                unset($this->_cache['poll'][$val]);
                if (isset($this->_tree[$val])) {
                    $this->_setPolled($this->_tree[$val], false);
                }
                $removed = true;
            }
        }

        if ($removed) {
            $GLOBALS['prefs']->setValue('nav_poll', serialize($this->_cache['poll']));
            $GLOBALS['injector']->getInstance('IMP_Search')->createVINBOXFolder();
            $this->changed = true;
        }
    }

    /**
     * Does the user want to poll this mailbox for new/unseen messages?
     *
     * @param array $elt  A tree element.
     *
     * @return integer  True if the user wants to poll the element.
     */
    public function isPolled($elt)
    {
        return $GLOBALS['prefs']->getValue('nav_poll_all')
            ? true
            : ($elt['a'] & self::ELT_IS_POLLED);
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
     * @param array $elt  A tree element.
     *
     * @return integer  True if the element is marked as invisible.
     */
    public function isInvisible($elt)
    {
        return $elt['a'] & self::ELT_INVISIBLE;
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
        $this->changed = true;

        /* If we are switching from unsubscribed to subscribed, no need
         * to do anything (we just ignore unsubscribed stuff). */
        if ($unsub === false) {
            return;
        }

        /* If we are switching from subscribed to unsubscribed, we need
         * to add all unsubscribed elements that live in currently
         * discovered items. */
        $this->_trackdiff = false;
        $this->_insert($this->_getList(true), false);
        $this->_trackdiff = true;
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
        if (!$base) {
            Horde_Imap_Client_Sort::sortMailboxes($mbox, array('delimiter' => $this->_delimiter));
            return;
        }

        $basesort = $othersort = array();

        foreach ($mbox as $val) {
            if ($this->isNonImapElt($this->_tree[$val])) {
                $othersort[$val] = $this->_tree[$val]['l'];
            } else {
                $basesort[$val] = $this->_tree[$val]['l'];
            }
        }

        /* Sort IMAP mailboxes. INBOX always occurs first. */
        natcasesort($basesort);
        unset($basesort['INBOX']);
        $mbox = array_merge(array('INBOX'), array_keys($basesort));

        /* Sort non-IMAP elements. */
        if (!empty($othersort)) {
            natcasesort($othersort);
            $mbox = array_merge($mbox, array_keys($othersort));
        }
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
        return (strcasecmp($name, 'INBOX') == 0) ? 'INBOX' : $name;
    }

    /**
     * Get namespace info for a full folder path.
     *
     * @param string $mailbox  The folder path.
     *
     * @return mixed  The namespace info for the folder path or null if the
     *                path doesn't exist.
     */
    protected function _getNamespace($mailbox)
    {
        if (!in_array($mailbox, array(self::OTHER_KEY, self::SHARED_KEY, self::VFOLDER_KEY)) &&
            (strpos($mailbox, self::VFOLDER_KEY . $this->_delimiter) !== 0)) {
            return $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->getNamespace($mailbox);
        }
        return null;
    }

    /**
     * Set the start point for determining element differences via eltDiff().
     */
    public function eltDiffStart()
    {
        $this->_eltdiff = array(
            'a' => array(),
            'c' => array(),
            'd' => array()
        );
    }

    /**
     * Return the list of elements that have changed since eltDiffStart()
     * was last called.
     *
     * @return array  Returns false if no changes have occurred, or an array
     *                with the following keys:
     * <pre>
     * 'a' => A list of elements that have been added.
     * 'c' => A list of elements that have been changed.
     * 'd' => A list of elements that have been deleted.
     * </pre>
     */
    public function eltDiff()
    {
        if (is_null($this->_eltdiff) || !$this->changed) {
            return false;
        }

        $ret = array(
            'a' => array_keys($this->_eltdiff['a']),
            'c' => array_keys($this->_eltdiff['c']),
            'd' => array_keys($this->_eltdiff['d'])
        );

        $this->_eltdiff = null;

        return $ret;
    }

    /**
     * Inserts virtual folders into the tree.
     *
     * @param array $id_list  An array with the folder IDs to add as the key
     *                        and the labels as the value.
     */
    public function insertVFolders($id_list)
    {
        if (empty($id_list) ||
            empty($GLOBALS['conf']['user']['allow_folders'])) {
            return;
        }

        $adds = $id = array();

        foreach ($id_list as $key => $val) {
            $id[$GLOBALS['injector']->getInstance('IMP_Search')->createSearchID($key)] = $val;
        }

        foreach (array_keys($id) as $key) {
            $id_key = self::VFOLDER_KEY . $this->_delimiter . $key;
            if (!isset($this->_tree[$id_key])) {
                $adds[] = $id_key;
            }
        }

        if (empty($adds)) {
            return;
        }

        $this->insert($adds);

        foreach ($id as $key => $val) {
            $this->_tree[$key]['l'] = $val;
        }

        /* Sort the Virtual Folder list in the object, if necessary. */
        if (!$this->_needSort($this->_tree[self::VFOLDER_KEY])) {
            return;
        }

        $vsort = array();
        foreach ($this->_parent[self::VFOLDER_KEY] as $val) {
            $vsort[$val] = $this->_tree[$val]['l'];
        }
        natcasesort($vsort);
        $this->_parent[self::VFOLDER_KEY] = array_keys($vsort);
        $this->_setNeedSort($this->_tree[self::VFOLDER_KEY], false);
        $this->changed = true;
    }

    /**
     * Returns whether this element is a virtual folder.
     *
     * @param array $elt  A tree element.
     *
     * @return integer  True if the element is a virtual folder.
     */
    public function isVFolder($elt)
    {
        return $elt['a'] & self::ELT_VFOLDER;
    }

    /**
     * Rename a current folder.
     *
     * @param array $old  The old folder names.
     * @param array $new  The new folder names.
     */
    public function rename($old, $new)
    {
        foreach ($old as $key => $val) {
            $polled = isset($this->_tree[$val])
                ? $this->isPolled($this->_tree[$val])
                : false;
            if ($this->delete($val)) {
                $this->insert($new[$key]);
                if ($polled) {
                    $this->addPollList($new[$key]);
                }
            }
        }
    }

    /**
     * Return the list of 'special' mailboxes.
     *
     * @return array  A list of folders, with keys of 'draft', 'sent', 'spam',
     *                and 'trash' and values containing the mailbox names
     *                ('sent' contains a list of mailbox names).
     */
    public function getSpecialMailboxes()
    {
        global $prefs;

        $identity = $GLOBALS['injector']->getInstance('IMP_Identity');

        return array(
            'draft' => IMP::folderPref($prefs->getValue('drafts_folder'), true),
            'sent' => $identity->getAllSentmailFolders(),
            'spam' => IMP::folderPref($prefs->getValue('spam_folder'), true),
            'trash' => IMP::folderPref($prefs->getValue('trash_folder'), true)
        );
    }

    /**
     * Sort a level in the tree.
     *
     * @param string $id  The parent folder whose children need to be sorted.
     */
    protected function _sortLevel($id)
    {
        if ($this->_needSort($this->_tree[$id])) {
            $this->_sortList($this->_parent[$id], ($id === self::BASE_ELT));
            $this->_setNeedSort($this->_tree[$id], false);
            $this->changed = true;
        }
    }

    /**
     * Determines the mailbox name to create given a parent and the new name.
     *
     * @param string $parent  The parent name (UTF7-IMAP).
     * @param string $parent  The new mailbox name (UTF7-IMAP).
     *
     * @return string  The full path to the new mailbox.
     * @throws IMP_Exception
     */
    public function createMailboxName($parent, $new)
    {
        $ns_info = empty($parent)
            ? $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->defaultNamespace()
            : $this->_getNamespace($parent);

        if (is_null($ns_info)) {
            if ($this->isNamespace($this->_tree[$parent])) {
                $ns_info = $this->_getNamespace($new);
                if (in_array($ns_info['type'], array('other', 'shared'))) {
                    return $new;
                }
            }
            throw new IMP_Exception(_("Cannot directly create mailbox in this folder."));
        }

        $mbox = $ns_info['name'];
        if (!empty($parent)) {
            $mbox .= substr_replace($parent, '', 0, strlen($ns_info['name']));
            $mbox = rtrim($mbox, $ns_info['delimiter']) . $ns_info['delimiter'];
        }
        return $mbox . $new;
    }

    /**
     * Creates a Horde_Tree representation of the current tree (respecting
     * the current iterator filter).
     *
     * @param string|Horde_Tree $name  Either the tree name, or a Horde_Tree
     *                                 object to add nodes to.
     * @param array $opts              Additional options:
     * <pre>
     * 'checkbox' - (boolean) Display checkboxes?
     *              DEFAULT: false
     * 'editvfolder' - (boolean) Display vfolder edit links?
     *                 DEFAULT: false
     * 'parent' - (string) The parent object of the current level.
     *            DEFAULT: null (add to base level)
     * 'poll_info' - (boolean) Include poll information?
     *               DEFAULT: false
     * 'render_type' - (string) The renderer name.
     *                 DEFAULT: Javascript
     * </pre>
     *
     * @return Horde_Tree  The tree object.
     */
    public function createTree($name, array $opts = array())
    {
        $opts = array_merge(array(
            'render_type' => 'Javascript'
        ), $opts);

        $this->recent = array();
        $this->unseen = 0;

        if ($name instanceof Horde_Tree_Base) {
            $tree = $name;
            $parent = $opts['parent'];
        } else {
            $tree = $GLOBALS['injector']->getInstance('Horde_Tree')->getTree($name, $opts['render_type'], array(
                'alternate' => true,
                'lines' => true,
                'lines_base' => true,
                'nosession' => true
            ));
            $parent = null;
        }

        $mailbox_url = (IMP::getViewMode() == 'mimp')
            ? Horde::applicationUrl('mailbox-mimp.php')
            : Horde::applicationUrl('mailbox.php');

        foreach ($this as $val) {
            $after = '';
            $params = array();

            switch ($opts['render_type']) {
            case 'IMP_Tree_Flist':
                $is_open = true;
                $label = $val->name;
                $params['orig_label'] = $val->label;
                break;

            case 'Javascript':
                $is_open = $val->is_open;
                $label = $val->name;
                $icon = $val->icon;
                $params['icon'] = $icon->icon;
                $params['iconopen'] = $icon->iconopen;
                break;

            case 'Simplehtml':
                $is_open = true;
                $label = htmlspecialchars(Horde_String::abbreviate($val->label, 30 - ($val->level * 2)));
                break;
            }

            if (!empty($opts['poll_info']) && $val->polled) {
                $poll_info = $val->poll_info;

                if ($poll_info->unseen) {
                    $this->unseen += $poll_info->unseen;
                    if ($poll_info->recent) {
                        $recent[$val->value] = $poll_info->recent;
                    }

                    $label = '<strong>' . $label . '</strong>';
                }

                $after = '&nbsp;(' . $poll_info->unseen . '/' . $poll_info->msgs . ')';
            }

            if (!$val->container) {
                $params['url'] = $mailbox_url->add('mailbox', $val->value);

                if ($this->_showunsub && !$val->sub) {
                    $params['class'] = 'folderunsub';
                }
            }

            $checkbox = '<input type="checkbox" class="checkbox" name="folder_list[]" value="' . IMP::formMbox($val->value, true) . '"';

            if ($val->vfolder) {
                $checkbox .= ' disabled="disabled"';

                if (!empty($opts['editvfolder']) && $val->editvfolder) {
                    $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
                    $after = '&nbsp[' .
                        $imp_search->deleteUrl($val->value)->link(array('title' => _("Delete Virtual Folder"))) . _("Delete") . '</a>'.
                        ']&nbsp;|&nbsp|[' .
                        $imp_search->editUrl($val->value)->link(array('title' => _("Edit Virtual Folder"))) . _("Edit") . '</a>'.
                        ']';
                    exit;
                }
            }

            $tree->addNode(
                strval($parent) . $val->value,
                ($val->level) ? strval($parent) . $val->parent : $parent,
                $label,
                $val->level,
                $is_open,
                $params,
                $after,
                empty($opts['checkbox']) ? null : $checkbox . ' />'
            );
        }

        return $tree;
    }

    /* ArrayAccess methods. */

    public function offsetExists($offset)
    {
        return isset($this->_tree[$this->_convertName($offset)]);
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset)
            ? new IMP_Imap_Tree_Element($this->_tree[$this->_convertName($offset)], $this)
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
        if (!($curr = $this->current())) {
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
            if ($GLOBALS['prefs']->getValue('tree_view') &&
                $curr->namespace &&
                !$curr->nonimap &&
                ($this->_tree[$curr->parent] && self::ELT_NOSHOW)) {
                $this->next();
            }
        } else {
            /* Else, increment within the current subfolder. */
            ++$this->_currkey;

            /* Descend tree until we reach a level that has more leaves we
             * have not yet traversed. */
            while (!($curr = $this->current()) &&
                   (!isset($c['samelevel']) ||
                    ($c['samelevel'] != $this->_currparent)) &&
                   ($parent = $this->_getParent($this->_currparent, true))) {
                list($this->_currparent, $this->_currkey) = $parent;
            }
        }

        if ($curr) {
            if (!$this->_activeElt($curr)) {
                $this->next();
            }
        } else {
            /* If we don't have a current element by this point, we have run
             * off the end of the tree. */
            $this->_currkey = null;
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
        if (!$this->_showunsub && ($c['mask'] & self::FLIST_UNSUB)) {
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
                } else {
                    $this->_currparent = $tmp->parent;
                    $this->_currkey = array_search($tmp->value, $this->_parent[$tmp->parent]);

                    if ($c['mask'] & self::FLIST_SAMELEVEL) {
                        $this->_currkey = 0;
                        $c['samelevel'] = $tmp->parent;
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
     * @param integer $mask  A mask with the following possible elements:
     * <pre>
     * IMP_Imap_Tree::FLIST_NOCONTAINER - Don't include container elements.
     * IMP_Imap_Tree::FLIST_UNSUB - Include unsubscribed elements.
     * IMP_Imap_Tree::FLIST_VFOLDER - Include Virtual Folders.
     * IMP_Imap_Tree::FLIST_NOCHILDREN - Don't include child elements.
     * IMP_Imap_Tree::FLIST_EXPANDED - Only include expanded folders.
     * ---
     * These options require that $base be set:
     * IMP_Imap_Tree::FLIST_ANCESTORS - Include ancestors of $base.
     * IMP_Imap_Tree::FLIST_SAMELEVEL - Include all mailboxes at the same
     *                                  level as $base.
     * IMP_Imap_Tree::FLIST_NOBASE - Don't include $base in the return.
     * </pre>
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
     * @param IMP_Imap_Tree_Element $elt  A tree element.
     *
     * @return boolean  True if it is an active element.
     */
    protected function _activeElt($elt, $child_check = false)
    {
        /* Skip invisible elements. */
        if ($elt->invisible) {
            return false;
        }

        $c = &$this->_cache['filter'];

        /* Skip virtual folders unless told to display them. */
        if ($elt->vfolder && !($c['mask'] & self::FLIST_VFOLDER)) {
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
                ($c['ancestors'][$this->_currparent] == $this->_currkey)) {
                return true;
            }

            /* If expanded is requested, we assume it overrides nochildren. */
            if ($c['mask'] & self::FLIST_EXPANDED) {
                return $elt->is_open;
            }

            if ($c['mask'] & self::FLIST_NOCHILDREN) {
                return false;
            }
        } else {
            /* Checks done when determining whether to mark current element as
             * valid. */
            if ($elt->container) {
                if (($c['mask'] & self::FLIST_NOCONTAINER) ||
                    !$elt->children) {
                    return false;
                }
            } elseif (!$this->_showunsub && !$elt->sub) {
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

        $p = $this[$parent]->parent;

        return array(
            $p,
            array_search($parent, $this->_parent[$p]) + ($inc ? 1 : 0)
        );
    }

}
