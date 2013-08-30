<?php
/**
 * Copyright 2000-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2000-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Provides a tree view of the mailboxes on an IMAP server (a/k/a a folder
 * list; in IMP, folders = collection of mailboxes), along with other display
 * elements (Remote Accounts; Virtual Folders).
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Anil Madhavapeddy <avsm@horde.org>
 * @author    Jon Parise <jon@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2000-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read boolean $changed  Has the tree changed?
 * @property-read IMP_Imap_Tree_Prefs_Expanded $expanded  The expanded folders
 *                                                        list.
 * @property-read IMP_Imap_Tree_Prefs_Poll $poll  The poll list.
 * @property-read boolean $showunsub  True if showing unsubscribed mailboxes.
 */
class IMP_Imap_Tree implements ArrayAccess, Countable, Iterator, Serializable
{
    /* Constants for mailboxElt attributes. */
    const ELT_NOSELECT = 1;
    const ELT_NAMESPACE = 2;
    const ELT_IS_OPEN = 4;
    const ELT_IS_SUBSCRIBED = 8;
    const ELT_NOINFERIORS = 16;
    const ELT_IS_POLLED = 32;
    const ELT_NOT_POLLED = 64;
    const ELT_VFOLDER = 128;
    const ELT_NONIMAP = 256;
    const ELT_INVISIBLE = 512;
    const ELT_NEED_SORT = 1024;
    const ELT_REMOTE = 2048;
    const ELT_REMOTE_AUTH = 4096;
    const ELT_REMOTE_MBOX = 8192;

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
    const FLIST_REMOTE = 2048;

    /* The string used to indicate the base of the tree. This must include
     * null since this is the only 7-bit character not allowed in IMAP
     * mailboxes (nulls allow us to sort by name but never conflict with an
     * IMAP mailbox). */
    const BASE_ELT = "base\0";

    /* Virtual folder key. */
    const VFOLDER_KEY = "vfolder\0";

    /* Remote account key. */
    const REMOTE_KEY = "remote\0";

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
     * Account sources.
     *
     * @var array
     */
    protected $_accounts;

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
     * Array containing the mailbox elements.
     *
     * @var array
     */
    protected $_elts;

    /**
     * List of element changes.
     *
     * @var array
     */
    protected $_eltdiff;

    /**
     * True if display_folder hook is not available.
     *
     * @var boolean
     */
    protected $_nohook = false;

    /**
     * Parent/child list.
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
     * Temporary data that is not saved across serialization.
     *
     * @var array
     */
    protected $_temp = array(
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
        $this->init();
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'changed':
            return $this->_changed;

        case 'expanded':
            if (!isset($this->_temp['expanded'])) {
                $this->_temp['expanded'] = new IMP_Imap_Tree_Prefs_Expanded();
            }
            return $this->_temp['expanded'];

        case 'poll':
            if (!isset($this->_temp['poll'])) {
                $this->_temp['poll'] = new IMP_Imap_Tree_Prefs_Poll();
            }
            return $this->_temp['poll'];

        case 'showunsub':
            return $this->_showunsub;
        }
    }

    /**
     * Initialize the tree.
     */
    public function init()
    {
        global $injector, $prefs, $session;

        $access_folders = $injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS);

        /* Reset class variables to the defaults. */
        $this->_accounts = $this->_elts = $this->_parent = array();
        $this->_changed = true;
        $this->_currkey = $this->_currparent = null;
        $this->_showunsub = (!$access_folders || !$prefs->getValue('subscribe') || $session->get('imp', 'showunsub'));
        $this->_resetEltDiff();

        /* Don't track initialization. */
        $old_track = $this->track;
        $this->track = false;

        /* Create a placeholder element to the base of the tree so we can
         * keep track of whether the base level needs to be sorted. */
        $this->_elts[self::BASE_ELT] = self::ELT_NEED_SORT | self::ELT_NONIMAP;
        $this->_parent[self::BASE_ELT] = array();

        $mask = IMP_Imap_Tree_Account::INIT;
        if ($this->_showunsub) {
            $mask |= IMP_Imap_Tree_Account::UNSUB;
        }

        /* Add base account. */
        $ob = $this->_accounts[self::BASE_ELT] = $access_folders
            ? new IMP_Imap_Tree_Account_Imap()
            : new IMP_Imap_Tree_Account_Inboxonly();
        array_map(array($this, '_insertElt'), $ob->getList($mask));

        /* Add remote servers. */
        $this->insert(iterator_to_array($injector->getInstance('IMP_Remote')));

        /* Add virtual folders. */
        $imp_search = $injector->getInstance('IMP_Search');
        $imp_search->setIteratorFilter(IMP_Search::LIST_VFOLDER);
        $this->insert(iterator_to_array($imp_search));

        $this->track = $old_track;
    }

    /**
     * Insert an element into the tree.
     *
     * @param mixed $id  The name of the mailbox (or a list of mailboxes),
     *                   an IMP_Search_Vfolder object, an IMP_Remote_Account
     *                   object, or an array containing any mixture of these.
     */
    public function insert($id)
    {
        if (!is_array($id)) {
            $id = array($id);
        }

        $to_insert = array();

        foreach ($id as $val) {
            if ($val instanceof IMP_Search_Vfolder) {
                /* Virtual Folders. */
                if (!$val->enabled) {
                    continue;
                }
                $key = self::VFOLDER_KEY;
                $base_mask = $elt_mask = self::ELT_VFOLDER;
            } elseif ($val instanceof IMP_Remote_Account) {
                /* Remote accounts. */
                $key = self::REMOTE_KEY;
                $base_mask = $elt_mask = self::ELT_REMOTE;
                if ($val->imp_imap->init) {
                    $elt_mask |= self::ELT_REMOTE_AUTH;
                }
                $this->_accounts[strval($val)] = new IMP_Imap_Tree_Account_Remote($val);
            } else {
                $to_insert[strval($this->getAccount($val))] = $this->_normalize($val);
                $key = null;
            }

            if (!is_null($key) && !isset($this[$val])) {
                if (!isset($this->_elts[$key])) {
                    $this->_insertElt(array(
                        'a' => $base_mask | self::ELT_NOSELECT | self::ELT_NONIMAP,
                        'v' => $key
                    ));
                }

                $elt = $this->_insertElt(array(
                    'a' => $elt_mask | self::ELT_IS_SUBSCRIBED | self::ELT_NONIMAP,
                    'p' => $key,
                    'v' => $val
                ));

                if ($elt_mask & self::ELT_REMOTE_AUTH) {
                    $this->insert($this->_accounts[strval($val)]);
                }
            }
        }

        foreach ($to_insert as $key => $val) {
            /* We want to add from the BASE of the tree up for efficiency
             * sake. */
            $this->_sortList($val);
            array_map(array($this, '_insertElt'), $this->_accounts[$key]->getList($val));
        }
    }

    /**
     * Expand an element.
     *
     * @param mixed $elts         The element (or an array of elements) to
     *                            expand.
     * @param boolean $expandall  Expand all subelements?
     */
    public function expand($elts, $expandall = false)
    {
        foreach ((is_array($elts) ? $elts : array($elts)) as $val) {
            if (($elt = $this[$val]) && $elt->children) {
                if (!$elt->open) {
                    $elt->open = true;
                }

                /* Expand all children beneath this one. */
                if ($expandall) {
                    $this->expand($this->_parent[strval($elt)]);
                }
            }
        }
    }

    /**
     * Expand all elements.
     */
    public function expandAll()
    {
        $this->expand($this->_parent[self::BASE_ELT], true);
    }

    /**
     * Collapse an element.
     *
     * @param mixed $elts  The element (or an array of elements) to expand.
     */
    public function collapse($elts)
    {
        foreach ((is_array($elts) ? $elts : array($elts)) as $val) {
            if ($elt = $this[$val]) {
                $elt->open = false;
            }
        }
    }

    /**
     * Collapse all elements.
     */
    public function collapseAll()
    {
        $this->collapse(
            array_diff_key(array_keys($this->_elts), array(self::BASE_ELT))
        );
    }

    /**
     * Delete an element from the tree.
     *
     * @param mixed $elts  The element (or an array of elements) to delete.
     */
    public function delete($id)
    {
        if (is_array($id)) {
            /* We want to delete from the TOP of the tree down to ensure that
             * parents have an accurate view of what children are left. */
            $this->_sortList($id);
            $id = array_reverse($id);
        } else {
            $id = array($id);
        }

        foreach ($id as $val) {
            if (!($elt = $this[$val])) {
                continue;
            }

            if ($elt->vfolder) {
                $parent = $this->_delete($elt);

                /* Rebuild the parent tree. */
                if (empty($this->_parent[$parent])) {
                    $this->delete($parent);
                } else {
                    $this->_parent[$parent] = array_values($this->_parent[$parent]);
                }
                $this->_changed = true;
            } else {
                if ($elt->inbox || $elt->namespace) {
                    continue;
                }

                $this->_changed = true;

                /* Do not delete from tree if there are child elements -
                 * instead, convert to a container element. */
                if ($elt->children) {
                    $elt->container = true;
                    continue;
                }

                $parent = $this->_delete($elt);

                if (empty($this->_parent[$parent])) {
                    /* This mailbox is now completely empty (no children). */
                    unset($this->_parent[$parent]);
                    if ($p_elt = $this[$parent]) {
                        if ($p_elt->container && !$p_elt->namespace) {
                            $this->delete($parent);
                        } else {
                            $p_elt->open = false;
                            $this->_addEltDiff($parent, 'c');
                        }
                    }
                } else {
                    /* Rebuild the parent tree. */
                    $this->_parent[$parent] = array_values($this->_parent[$parent]);

                    if (!$this[$parent]->children) {
                        $this->_addEltDiff($parent, 'c');
                    }
                }

                /* Remove the mailbox from the expanded folders list. */
                unset($this->expanded[$val]);

                /* Remove the mailbox from the nav_poll list. */
                $this->removePollList($val);
            }
        }
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

        $this->setIteratorFilter(self::FLIST_NOCONTAINER | self::FLIST_UNSUB | self::FLIST_ASIS, $old);
        $old_list = iterator_to_array($this);

        foreach ($old_list as $val) {
            $new_list[] = $new_name = substr_replace($val, $new, 0, strlen($old));
            if ($val->polled) {
                $polled[] = $new_name;
            }
        }

        $this->insert($new_list);
        $this->addPollList($polled);
        $this->delete($old_list);
    }

    /**
     * Subscribe an element to the tree.
     *
     * @param mixed $id  The element name or an array of element names.
     */
    public function subscribe($id)
    {
        foreach ((is_array($id) ? $id : array($id)) as $val) {
            $this->setAttribute('subscribed', $val, true);
            $this->setAttribute('container', $val, false);
        }
    }

    /**
     * Unsubscribe an element from the tree.
     *
     * @param mixed $id  The element name or an array of element names.
     */
    public function unsubscribe($id)
    {
        if (is_array($id)) {
            /* We want to delete from the TOP of the tree down to ensure that
             * parents have an accurate view of what children are left. */
            $this->_sortList($id);
            $id = array_reverse($id);
        } else {
            $id = array($id);
        }

        foreach ($id as $val) {
            /* INBOX can never be unsubscribed to. */
            if (($elt = $this[$val]) && !$elt->inbox) {
                $this->_changed = true;

                /* Do not delete from tree if there are child elements -
                 * instead, convert to a container element. */
                if (!$this->showunsub && $elt->children) {
                    $this->setAttribute('container', $elt, true);
                }

                /* Set as unsubscribed, add to unsubscribed list, and remove
                 * from subscribed list. */
                $this->setAttribute('subscribed', $elt, false);
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
        if ((bool)$unsub === $this->showunsub) {
            return;
        }

        $this->_changed = true;
        $this->_showunsub = (bool)$unsub;

        /* If we are switching from unsubscribed to subscribed, no need
         * to do anything (we just ignore unsubscribed stuff). */
        if (($this->_showunsub === false) ||
            $this[self::BASE_ELT]->subscribed) {
            return;
        }

        /* The BASE_ELT having the SUBSCRIBED mask indicates the unsubscribed
         * mailboxes have been loaded into the object. */
        $this->setAttribute('subscribed', self::BASE_ELT, true);

        /* If we are switching from subscribed to unsubscribed, we need
         * to add all unsubscribed elements that live in currently
         * discovered items. */
        $old_track = $this->track;
        $this->track = false;
        foreach ($this->_accounts as $val) {
            array_map(array($this, '_insertElt'), $val->getList($val::UNSUB));
        }
        $this->track = $old_track;
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
        $plist = array();

        foreach ($this as $val) {
            if ($val->polled) {
                $plist[] = strval($val);
            }
        }

        if ($sort) {
            $this->_sortList($plist, true);
        }

        return IMP_Mailbox::get(array_filter($plist));
    }

    /**
     * Add elements to the poll list.
     *
     * @param mixed $id  The element name or a list of element names to add.
     */
    public function addPollList($id)
    {
        if ($this->poll->locked) {
            return;
        }

        foreach ((is_array($id) ? $id : array($id)) as $val) {
            if ($elt = $this[$val]) {
                continue;
            }

            if (!$elt->polled && !$elt->nonimap && !$elt->container) {
                if (!$elt->subscribed) {
                    $elt->subscribed = true;
                }
                $elt->polled = true;
                $this->setAttribute('polled', $elt, true);
            }
        }
    }

    /**
     * Remove elements from the poll list.
     *
     * @param mixed $id  The element name or a list of element names to
     *                   remove.
     */
    public function removePollList($id)
    {
        $poll = $this->poll;
        if (!$poll->locked) {
            foreach (IMP_Mailbox::get(is_array($id) ? $id : array($id)) as $val) {
                if (!$val->inbox) {
                    unset($poll[strval($val)]);
                    $this->setAttribute('polled', $val, false);
                }
            }
        }
    }

    /**
     * Get an attribute value.
     *
     * @param string $type  The attribute type.
     * @param string $name  The element name.
     *
     * @return mixed  Boolean attribute result, or null if element or
     *                attribute doesn't exist
     */
    public function getAttribute($type, $name)
    {
        if (!($elt = $this[$name])) {
            return null;
        }
        $s_elt = strval($elt);

        switch ($type) {
        case 'children':
        case 'children_filter':
            if (isset($this->_parent[$s_elt])) {
                foreach ($this->_parent[$s_elt] as $val) {
                    $v = $this[$val];

                    if ($v->subscribed ||
                        ($this->showunsub &&
                         !$v->container &&
                         !$v->namespace)) {
                        /* If skipping special mailboxes, need to check an
                         * element for at least one non-special child. */
                        if (($type == 'children') ||
                            !($this->_temp['filter']['mask'] & self::FLIST_NOSPECIALMBOXES) ||
                            !$v->mbox_ob->special) {
                            return true;
                        }
                    }

                    /* So this element is not a visible child, but its
                     * children may still be. */
                    if ($v->$type) {
                        return true;
                    }
                }
            }
            return false;

        case 'container':
            return (($this->_elts[$s_elt] & self::ELT_NOSELECT) ||
                    (((!$this->showunsub && !$elt->subscribed) || $elt->invisible) &&
                     $elt->children_filter));

        case 'invisible':
            $attr = self::ELT_INVISIBLE;
            break;

        case 'namespace':
            $attr = self::ELT_NAMESPACE;
            break;

        case 'needsort':
            if (count($this->_parent[$s_elt]) < 2) {
                return false;
            }
            $attr = self::ELT_NEED_SORT;
            break;

        case 'nochildren':
            $attr = self::ELT_NOINFERIORS;
            break;

        case 'nonimap':
            $attr = self::ELT_NONIMAP;
            break;

        case 'open':
            if (!$elt->children_filter) {
                return false;
            }
            $attr = self::ELT_IS_OPEN;
            break;

        case 'polled':
            if ($this->_elts[$s_elt] & self::ELT_IS_POLLED) {
                return true;
            } elseif ($this->_elts[$s_elt] & self::ELT_NOT_POLLED) {
                return false;
            }
            return (!$this->poll[$elt] || $elt->nonimap || $elt->container);

        case 'remote':
            $attr = self::ELT_REMOTE;
            break;

        case 'remote_auth':
            $attr = self::ELT_REMOTE_AUTH;
            break;

        case 'remote_mbox':
            $attr = self::ELT_REMOTE_MBOX;
            break;

        case 'subscribed':
            if ($elt->inbox) {
                return true;
            }
            $attr = self::ELT_IS_SUBSCRIBED;
            break;

        case 'vfolder':
            $attr = self::ELT_VFOLDER;
            break;

        default:
            return null;
        }

        return ($this->_elts[$s_elt] & $attr);
    }

    /**
     * Change an attribute value.
     *
     * @param string $type   The attribute type.
     * @param string $elt    The element name.
     * @param boolean $bool  The boolean value.
     */
    public function setAttribute($type, $elt, $bool)
    {
        if (!($elt = $this[$elt])) {
            return;
        }

        $attr = null;
        $s_elt = strval($elt);

        switch ($type) {
        case 'container':
            $attr = self::ELT_NOSELECT;
            $this->_addEltDiff($elt, 'c');
            break;

        case 'invisible':
            $attr = self::ELT_INVISIBLE;
            break;

        case 'needsort':
            $attr = self::ELT_NEED_SORT;
            break;

        case 'open':
            $attr = self::ELT_IS_OPEN;
            if ($bool) {
                $this->expanded[$elt] = true;
            } else {
                unset($this->expanded[$elt]);
            }
            break;

        case 'polled':
            if ($bool) {
                $add = self::ELT_IS_POLLED;
                $remove = self::ELT_NOT_POLLED;
            } else {
                $add = self::ELT_NOT_POLLED;
                $remove = self::ELT_IS_POLLED;
            }
            $this->_elts[$s_elt] |= $add;
            $this->_elts[$s_elt] &= ~$remove;
            return;

        case 'subscribed':
            $attr = self::ELT_IS_SUBSCRIBED;
            break;

        default:
            return;
        }

        if ($bool) {
            $this->_elts[$s_elt] |= $attr;
        } else {
            $this->_elts[$s_elt] &= ~$attr;
        }

        $this->_changed = true;
    }

    /**
     * Get the account object for a given element ID.
     *
     * @param string $id  Element ID.
     *
     * @return IMP_Imap_Tree_Account  Account object.
     */
    public function getAccount($id)
    {
        foreach (array_diff(array_keys($this->_accounts), array(self::BASE_ELT)) as $val) {
            if (strpos($id, $val) === 0) {
                return $this->_accounts[$val];
            }
        }

        return $this->_accounts[self::BASE_ELT];
    }

    /**
     * Get the parent element for a given element ID.
     *
     * @param string $id  Element ID.
     *
     * @return mixed  IMP_Imap_Tree_Element object, or null if no parent.
     */
    public function getParent($id)
    {
        $id = strval($id);

        if ($id == self::BASE_ELT) {
            return null;
        }

        foreach ($this->_parent as $key => $val) {
            if (in_array($id, $val, true)) {
                return $this[$key];
            }
        }

        return $this[self::BASE_ELT];
    }

    /**
     * Explicitly mark an element as added.
     *
     * @param string $id  Element ID.
     */
    public function addEltDiff($id)
    {
        $this->_addEltDiff($id, 'a');
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

        $poll = $result = array();

        if (!empty($changes['a'])) {
            $result['a'] = array();
            foreach (array_keys($changes['a']) as $val) {
                $result['a'][] = $this->_ajaxElt($val);
                $poll[] = $val;
            }
        }

        if (!empty($changes['c'])) {
            $result['c'] = array();
            // Skip the base element, since any change there won't ever be
            // updated on-screen.
            foreach (array_diff(array_keys($changes['c']), array(self::BASE_ELT)) as $val) {
                $result['c'][] = $this->_ajaxElt($val);
                $poll[] = $val;
            }
        }

        if (!empty($changes['d'])) {
            $result['d'] = array();
            foreach (array_reverse(array_keys($changes['d'])) as $val) {
                $result['d'][] = IMP_Mailbox::get($val)->form_to;
            }
        }

        $GLOBALS['injector']->getInstance('IMP_Ajax_Queue')->poll($poll);

        return $result;
    }

    /* Internal methods. */

    /**
     * Deletes an element and returns its parent.
     *
     * @param string $id  Element ID.
     *
     * @return string  Parent ID.
     */
    protected function _delete($id)
    {
        $parent = strval($this[$id]->parent);
        $this->_addEltDiff($id, 'd');

        /* Delete the entry from the parent tree. */
        unset(
            $this->_elts[strval($id)],
            $this->_parent[$parent][array_search(strval($id), $this->_parent[$parent], true)]
        );

        return $parent;
    }

    /**
     * Normalize an element ID to the correct, internal name.
     *
     * @param string $id  The element ID.
     *
     * @return string  The converted name.
     */
    protected function _normalize($id)
    {
        $id = strval($id);

        return (strcasecmp($id, 'INBOX') == 0)
            ? 'INBOX'
            : $id;
    }

    /**
     * Mark an element as changed.
     *
     * @param string $id    Element ID.
     * @param string $type  Either 'a' (add), 'c' (changed), or 'd' (deleted).
     */
    protected function _addEltDiff($id, $type)
    {
        if (!$this->track || !($elt = $this[$id])) {
            return;
        }

        $ed = &$this->_eltdiff;
        $id = strval($elt);

        if (array_key_exists($id, $ed['o'])) {
            if (($type != 'd') && ($ed['o'][$id] == $elt)) {
                unset(
                    $ed['a'][$id],
                    $ed['c'][$id],
                    $ed['d'][$id],
                    $ed['o'][$id]
                );

                /* Check for virtual folder change. */
                if ($elt->vfolder) {
                    $ed['c'][$id] = 1;
                }
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
     * Insert an element into the tree.
     *
     * @param array $elt  Element data. Keys:
     * <pre>
     *   - a: (integer) Attributes.
     *   - p: (string) Parent element ID.
     *   - v: (string) Mailbox ID.
     * </pre>
     */
    protected function _insertElt($elt)
    {
        $name = $this->_normalize($elt['v']);
        if (isset($this->_elts[$name])) {
            return;
        }

        $p_elt = $this[isset($elt['p']) ? $elt['p'] : self::BASE_ELT];
        $parent = strval($p_elt);

        $this->_changed = true;

        $prev = $this->track
            ? $p_elt->children
            : null;

        $this->_parent[$parent][] = $name;
        $this->_elts[$name] = $elt['a'];

        /* Check for polled status. */
        $this->setAttribute('polled', $name, $this->poll[$name]);

        /* Check for expanded status. */
        $this->setAttribute('open', $name, $this->expanded[$name]);

        if (!$this->_nohook) {
            try {
                $this->setAttribute('invisible', $name, !Horde::callHook('display_folder', array($name), 'imp'));
            } catch (Horde_Exception_HookNotSet $e) {
                $this->_nohook = true;
            }
        }

        /* Make sure we are sorted correctly. */
        if (count($this->_parent[$parent]) > 1) {
            $this->setAttribute('needsort', $p_elt, true);
        }

        $this->_addEltDiff($name, 'a');
        if (!is_null($prev) && ($p_elt->children != $prev)) {
            $this->_addEltDiff($p_elt, 'c');
        }
    }

    /**
     * Sort a level in the tree.
     *
     * @param string $id  The parent element whose children need to be sorted.
     */
    protected function _sortLevel($id)
    {
        if (($elt = $this[$id]) && $elt->needsort) {
            $this->_sortList($this->_parent[strval($elt)], $elt->base_elt);
            $this->setAttribute('needsort', $elt, false);
        }
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
            $list_ob = new Horde_Imap_Client_Mailbox_List($mbox);
            $mbox = $list_ob->sort();
            return;
        }

        $basesort = $othersort = array();
        /* INBOX always appears first. */
        $sorted = array('INBOX');

        foreach ($mbox as $key => $val) {
            $ob = $this[$val];
            if ($ob->nonimap) {
                $othersort[$key] = $ob->mbox_ob->label;
            } elseif ($val !== 'INBOX') {
                $basesort[$key] = $ob->mbox_ob->label;
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
     *   - nc: (boolean) [no children] Does the element not allow children?
     *         DEFAULT: no
     *   - pa: (string) [parent] The parent element.
     *         DEFAULT: DimpCore.conf.base_mbox
     *   - po: (boolean) [polled] Is the element polled?
     *         DEFAULT: no
     *   - r: (integer) [remote] Is this a "remote" element? 1 is the remote
     *        container, 2 is a remote account, and 3 is a remote mailbox.
     *        DEFAULT: 0
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
        $mbox_ob = $elt->mbox_ob;

        $ob = new stdClass;

        if ($elt->children_filter) {
            $ob->ch = 1;
        }
        $ob->m = $mbox_ob->form_to;
        if ($elt->nochildren) {
            $ob->nc = 1;
        }

        $label = $mbox_ob->label;
        if ($ob->m != $label) {
            $ob->t = $label;
        }

        $tmp = htmlspecialchars($mbox_ob->abbrev_label);
        if ($ob->m != $tmp) {
            $ob->l = $tmp;
        }

        $parent = $elt->parent;
        if (!$parent->base_elt) {
            $ob->pa = $parent->mbox_ob->form_to;
        }

        if ($elt->vfolder) {
            $ob->v = $mbox_ob->editvfolder ? 2 : 1;
        }

        if ($elt->nonimap) {
            $ob->n = 1;
            if ($mbox_ob->remote_container) {
                $ob->r = 1;
            }
        }

        if ($elt->container) {
            $ob->cl = 'exp';
            $ob->co = 1;
        } else {
            if (!$elt->subscribed) {
                $ob->un = 1;
            }

            if (isset($ob->n) && isset($ob->r)) {
                $ob->r = ($mbox_ob->remote_auth ? 3 : 2);
            }

            if ($elt->polled) {
                $ob->po = 1;
            }

            if ($elt->inbox || $mbox_ob->special) {
                $ob->s = 1;
            } elseif (empty($ob->v) && !empty($ob->ch)) {
                $ob->cl = 'exp';
            }
        }

        $icon = $mbox_ob->icon;
        if ($icon->user_icon) {
            $ob->cl = 'customimg';
            $ob->i = strval($icon->icon);
        } else {
            $ob->cl = $icon->class;
        }

        return $ob;
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset)
    {
        return isset($this->_elts[$this->_normalize($offset)]);
    }

    /**
     * @return IMP_Imap_Tree_Element
     */
    public function offsetGet($offset)
    {
        if ($offset instanceof IMP_Imap_Tree_Element) {
            return $offset;
        }

        $offset = $this->_normalize($offset);
        return isset($this->_elts[$offset])
            ? new IMP_Imap_Tree_Element($offset, $this)
            : null;
    }

    /**
     */
    public function offsetSet($offset, $value)
    {
        $this->insert($offset);
    }

    /**
     */
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

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return serialize(array(
            // Serialized data ID.
            $this->_accounts,
            $this->_elts,
            $this->track ? $this->_eltdiff : null,
            $this->_parent,
            $this->showunsub
        ));
    }

    /**
     * @throws Exception
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data)) {
            throw new Exception('Cache version change');
        }

        list(
            $this->_accounts,
            $this->_elts,
            $eltdiff,
            $this->_parent,
            $this->showunsub
        ) = $data;

        $this->_eltdiff = is_null($eltdiff)
            ? $this->_resetEltDiff()
            : $eltdiff;
    }

    /**
     * Creates a Horde_Tree representation of the current tree (respecting
     * the current iterator filter).
     *
     * @param string|Horde_Tree $name  Either the tree name, or a Horde_Tree
     *                                 object to add nodes to.
     * @param array $opts              Additional options:
     * <pre>
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
     *   - poll_info: (boolean) Include poll information in output?
     *                DEFAULT: false
     *   - render_params: (array) List of params to pass to renderer if
     *                    auto-creating.
     *                    DEFAULT: 'alternate', 'lines', and 'lines_base'
     *                             are passed in with true values.
     *   - render_type: (string) The renderer name.
     *                  DEFAULT: Javascript
     * </pre>
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
            $elt_parent = null;
            $mbox_ob = $val->mbox_ob;
            $params = array();

            switch ($opts['render_type']) {
            case 'IMP_Tree_Flist':
                if ($mbox_ob->vfolder_container) {
                    continue 2;
                }

                $is_open = true;
                $label = $params['orig_label'] = empty($opts['basename'])
                    ? $mbox_ob->abbrev_label
                    : $mbox_ob->basename;
                break;

            case 'IMP_Tree_Jquerymobile':
                $is_open = true;
                $label = $mbox_ob->display_html;
                $icon = $mbox_ob->icon;
                $params['icon'] = $icon->icon;
                $params['special'] = $mbox_ob->inbox || $mbox_ob->special;
                $params['class'] = 'imp-folder';
                $params['urlattributes'] = array(
                    'id' => 'imp-mailbox-' . $mbox_ob->form_to
                );

                /* Force to flat tree so that non-polled parents don't cause
                 * polled children to be skipped by renderer (see Bug
                 * #11238). */
                $elt_parent = $this[self::BASE_ELT];
                break;

            case 'IMP_Tree_Simplehtml':
                $is_open = $val->open;
                if ($tree->shouldToggle($mbox_ob->form_to)) {
                    if ($is_open) {
                        $this->collapse($val);
                    } else {
                        $this->expand($val);
                    }
                    $is_open = !$is_open;
                }
                $label = htmlspecialchars(Horde_String::abbreviate($mbox_ob->abbrev_label, 30 - ($elt->level * 2)));
                break;

            case 'Javascript':
                $is_open = $val->open;
                $label = empty($opts['basename'])
                    ? htmlspecialchars($mbox_ob->abbrev_label)
                    : htmlspecialchars($mbox_ob->basename);
                $icon = $mbox_ob->icon;
                $params['icon'] = $icon->icon;
                $params['iconopen'] = $icon->iconopen;
                break;
            }

            if (!empty($opts['poll_info']) && $val->polled) {
                $poll_info = $mbox_ob->poll_info;

                if ($poll_info->unseen) {
                    switch ($opts['render_type']) {
                    case 'IMP_Tree_Jquerymobile':
                        $after = $poll_info->unseen;
                        break;

                    default:
                        $label = '<strong>' . $label . '</strong>&nbsp;(' .
                            $poll_info->unseen . ')';
                    }
                }
            }

            if ($val->container) {
                $params['container'] = true;
            } else {
                switch ($view) {
                case $registry::VIEW_MINIMAL:
                    $params['url'] = IMP_Minimal_Mailbox::url(array('mailbox' => $mbox_ob));
                    break;

                case $registry::VIEW_SMARTMOBILE:
                    $url = new Horde_Core_Smartmobile_Url();
                    $url->add('mbox', $mbox_ob->form_to);
                    $url->setAnchor('mailbox');
                    $params['url'] = strval($url);
                    break;

                default:
                    $params['url'] = $mbox_ob->url('mailbox')->setRaw(true);
                    break;
                }

                if ($this->showunsub && !$val->subscribed) {
                    $params['class'] = 'mboxunsub';
                }
            }

            $checkbox = empty($opts['checkbox'])
                ? ''
                : '<input type="checkbox" class="checkbox" name="mbox_list[]" value="' . $mbox_ob->form_to . '"';

            if ($val->nonimap) {
                $checkbox .= ' disabled="disabled"';
            }

            if ($val->vfolder &&
                !empty($opts['editvfolder']) &&
                $val->container) {
                $after = '&nbsp[' .
                    $registry->getServiceLink('prefs', 'imp')->add('group', 'searches')->link(array('title' => _("Edit Virtual Folder"))) . _("Edit") . '</a>'.
                    ']';
            }

            if (is_null($elt_parent)) {
                $elt_parent = $val->parent;
            }

            $tree->addNode(array(
                'id' => $mbox_ob->form_to,
                'parent' => $elt_parent->base_elt ? $parent : $elt_parent->mbox_ob->form_to,
                'label' => $label,
                'expanded' => isset($opts['open']) ? $opts['open'] : $is_open,
                'params' => $params,
                'right' => $after,
                'left' => empty($opts['checkbox']) ? null : $checkbox . ' />'
            ));
        }

        return $tree;
    }

    /* Iterator methods. */

    /**
     */
    public function current()
    {
        return $this->valid()
            ? $this[$this->_parent[$this->_currparent][$this->_currkey]]
            : null;
    }

    /**
     */
    public function key()
    {
        return $this->valid()
            ? $this->_parent[$this->_currparent][$this->_currkey]
            : null;
    }

    /**
     */
    public function next()
    {
        $curr = $this->current();
        if (is_null($curr)) {
            return;
        }

        $c = &$this->_temp['filter'];

        $old_showunsub = $this->showunsub;
        if ($c['mask'] & self::FLIST_UNSUB) {
            $this->_showunsub = true;
        }

        if ($this->_activeEltChild($curr)) {
            /* Store old pointer. */
            $this->_temp['iterator'][] = array(
                $this->_currkey + 1,
                $this->_currparent
            );

            /* Move into child element. */
            $this->_currkey = 0;
            $this->_currparent = strval($curr);
            $this->_sortLevel($curr);

            $curr = $this->current();
        } else {
            /* Else, increment within the current subfolder. */
            ++$this->_currkey;

            /* Descend tree until we reach a level that has more leaves we
             * have not yet traversed. */
            while ((($curr = $this->current()) === null) &&
                   !empty($this->_temp['iterator'])) {
                list($this->_currkey, $this->_currparent) = array_pop($this->_temp['iterator']);
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

    /**
     */
    public function rewind()
    {
        $this->_currkey = 0;
        $this->_currparent = self::BASE_ELT;
        $this->_sortLevel($this->_currparent);
        $this->_temp['iterator'] = array();

        $c = &$this->_temp['filter'];

        /* If showing unsubscribed, toggle subscribed flag to make sure we
         * have subscribed mailbox information. */
        if (!$this->showunsub &&
            ($c['mask'] & self::FLIST_UNSUB) &&
            !($c['mask'] & self::FLIST_ASIS)) {
            $this->showUnsubscribed(true);
            $this->showUnsubscribed(false);
        }

        // Search for base element.
        if ($c['base']) {
            if ($tmp = $this[$c['base']]) {
                if ($c['mask'] & self::FLIST_ANCESTORS) {
                    $c['ancestors'] = array(strval($tmp) => null);
                    while ($parent = $tmp->parent) {
                        $p = strval($parent);
                        $c['ancestors'][$p] = array_search($p, $this->_parent[$p], true);
                        $tmp = $parent;
                    }
                } elseif ($c['mask'] & self::FLIST_NOBASE) {
                    $this->_currparent = strval($tmp);

                    /* Check to make sure current element is valid. */
                    $curr = $this->current();
                    if (is_null($curr)) {
                        $this->_currkey = null;
                    } elseif (!$this->_activeElt($curr)) {
                        $this->next();
                    }
                } else {
                    $this->_currparent = strval($tmp->parent);
                    if (!($c['mask'] & self::FLIST_SAMELEVEL)) {
                        $this->_currkey = array_search(strval($tmp), $this->_parent[$this->_currparent]);
                    }
                }
            } else {
                $this->_currkey = null;
            }
        }
    }

    /**
     */
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
     *  <li>
     *   IMP_Imap_Tree::FLIST_REMOTE: Show remote accounts.
     *   Default: Remote accounts are ignored.
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
        $this->_temp['filter'] = array(
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
     * @return boolean  True if $elt is an active element.
     */
    protected function _activeElt($elt)
    {
        $c = &$this->_temp['filter'];

        /* Skip virtual folders unless told to display them. */
        if ($elt->vfolder) {
            return ($c['mask'] & self::FLIST_VFOLDER);
        }

        /* Show containers if NOCONTAINER is not set and children exist. */
        if ($elt->container) {
            if ($elt->remote) {
                if (!($c['mask'] & self::FLIST_REMOTE)) {
                    return false;
                }
            } elseif (($c['mask'] & self::FLIST_NOCONTAINER) ||
                      !$elt->children_filter) {
                return false;
            }
        } elseif ($elt->invisible) {
            /* Skip invisible elements (do after container check since it may
             * need to be shown as a container). */
            return false;
        } elseif (!$this->showunsub && !$elt->subscribed) {
            /* Don't show element if not subscribed. */
            return false;
        } elseif ($elt->mbox_ob->special) {
            /* Skip special mailboxes if requested. Otherwise, always show. */
            if ($c['mask'] & self::FLIST_NOSPECIALMBOXES) {
                return false;
            }
        } elseif (($c['mask'] & self::FLIST_POLLED) && !$elt->polled) {
            /* Skip non-polled mailboxes if requested. */
            return false;
        }

        return true;
    }

    /**
     * Determine whether an element has any "active" children.
     *
     * @param IMP_Mailbox $elt  A mailbox element.
     *
     * @return boolean  True if $elt contains active children.
     */
    protected function _activeEltChild($elt)
    {
        $c = &$this->_temp['filter'];

        /* This will catch Virtual Folders (they don't have children). */
        if (!isset($this->_parent[strval($elt)])) {
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
            return $elt->open;
        }

        /* Explicitly don't return child elements. */
        if ($c['mask'] & self::FLIST_NOCHILDREN) {
            return false;
        }

        return true;
    }

}
