<?php
/**
 * Copyright 2000-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2000-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * IMP_Ftree (folder tree) provides a tree view of the mailboxes on a backend
 * (a/k/a a folder list; in IMP, folders = collection of mailboxes), along
 * with other display elements (Remote Accounts; Virtual Folders).
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Anil Madhavapeddy <avsm@horde.org>
 * @author    Jon Parise <jon@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2000-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read boolean $changed  Has the tree changed?
 * @property-read IMP_Ftree_Eltdiff $eltdiff  Element diff tracker.
 * @property-read IMP_FTree_Prefs_Expanded $expanded  The expanded folders
 *                                                    list.
 * @property-read IMP_Ftree_Prefs_Poll $poll  The poll list.
 * @property-read boolean $subscriptions  Whether IMAP subscriptions are
 *                                        enabled.
 * @property-read boolean $unsubscribed_loaded  True if unsubscribed mailboxes
 *                                              have been loaded.
 */
class IMP_Ftree
implements ArrayAccess, Countable, IteratorAggregate, Serializable
{
    /* Constants for mailboxElt attributes. */
    const ELT_NOSELECT = 1;
    const ELT_NAMESPACE_OTHER = 2;
    const ELT_NAMESPACE_SHARED = 4;
    const ELT_IS_OPEN = 8;
    const ELT_IS_SUBSCRIBED = 16;
    const ELT_NOINFERIORS = 32;
    const ELT_IS_POLLED = 64;
    const ELT_NOT_POLLED = 128;
    const ELT_VFOLDER = 256;
    const ELT_NONIMAP = 512;
    const ELT_INVISIBLE = 1024;
    const ELT_NEED_SORT = 2048;
    const ELT_REMOTE = 4096;
    const ELT_REMOTE_AUTH = 8192;
    const ELT_REMOTE_MBOX = 16384;

    /* The string used to indicate the base of the tree. This must include
     * null since this is the only 7-bit character not allowed in IMAP
     * mailboxes (nulls allow us to sort by name but never conflict with an
     * IMAP mailbox). */
    const BASE_ELT = "base\0";

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
     * Element diff tracking.
     *
     * @var IMP_Ftree_Eltdiff
     */
    protected $_eltdiff;

    /**
     * Array containing the mailbox elements.
     *
     * @var array
     */
    protected $_elts;

    /**
     * Parent/child list.
     *
     * @var array
     */
    protected $_parent;

    /**
     * Temporary data that is not saved across serialization.
     *
     * @var array
     */
    protected $_temp = array();

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
        global $prefs;

        switch ($name) {
        case 'changed':
            return ($this->_changed || $this->eltdiff->changed);

        case 'expanded':
            if (!isset($this->_temp['expanded'])) {
                $this->_temp['expanded'] = new IMP_Ftree_Prefs_Expanded();
            }
            return $this->_temp['expanded'];

        case 'eltdiff':
            return $this->_eltdiff;

        case 'poll':
            if (!isset($this->_temp['poll'])) {
                $this->_temp['poll'] = new IMP_Ftree_Prefs_Poll($this);
            }
            return $this->_temp['poll'];

        case 'subscriptions':
            return $prefs->getValue('subscribe');

        case 'unsubscribed_loaded':
            return $this[self::BASE_ELT]->subscribed;
        }
    }

    /**
     * Initialize the tree.
     */
    public function init()
    {
        global $injector, $session;

        $access_folders = $injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS);

        /* Reset class variables to the defaults. */
        $this->_accounts = $this->_elts = $this->_parent = array();
        $this->_changed = true;

        $old_track = (isset($this->_eltdiff) && $this->_eltdiff->track);
        $this->_eltdiff = new IMP_Ftree_Eltdiff();

        /* Create a placeholder element to the base of the tree so we can
         * keep track of whether the base level needs to be sorted. */
        $this->_elts[self::BASE_ELT] = self::ELT_NEED_SORT | self::ELT_NONIMAP;
        $this->_parent[self::BASE_ELT] = array();

        $mask = IMP_Ftree_Account::INIT;
        if (!$access_folders || !$this->subscriptions || $session->get('imp', 'showunsub')) {
            $mask |= IMP_Ftree_Account::UNSUB;
            $this->setAttribute('subscribed', self::BASE_ELT, true);
        }

        /* Add base account. */
        $ob = $this->_accounts[self::BASE_ELT] = $access_folders
            ? new IMP_Ftree_Account_Imap()
            : new IMP_Ftree_Account_Inboxonly();
        array_map(array($this, '_insertElt'), $ob->getList(null, $mask));

        if ($access_folders) {
            /* Add remote servers. */
            $this->insert(iterator_to_array(
                $injector->getInstance('IMP_Remote')
            ));

            /* Add virtual folders to the tree. */
            $this->insert(iterator_to_array(
                IMP_Search_IteratorFilter::create(
                    IMP_Search_IteratorFilter::VFOLDER
                )
            ));
        }

        if ($old_track) {
            $this->eltdiff->track = true;
        }
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
        foreach ((is_array($id) ? $id : array($id)) as $val) {
            if (($val instanceof IMP_Search_Vfolder) &&
                !isset($this->_accounts[strval($val)])) {
                /* Virtual Folders. */
                $account = $this->_accounts[strval($val)] = new IMP_Ftree_Account_Vfolder($val);
            } elseif (($val instanceof IMP_Remote_Account) &&
                      !isset($this->_accounts[strval($val)])) {
                /* Remote accounts. */
                $account = $this->_accounts[strval($val)] = new IMP_Ftree_Account_Remote($val);
            } else {
                $account = $this->getAccount($val);
                $val = $this->_normalize($val);
            }

            array_map(array($this, '_insertElt'), $account->getList($val));
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
            $this->sortList($id);
            $id = array_reverse($id);
        } else {
            $id = array($id);
        }

        foreach (array_filter(array_map(array($this, 'offsetGet'), $id)) as $elt) {
            $account = $this->getAccount($elt);
            if (!($mask = $account->delete($elt))) {
                continue;
            }

            $this->_changed = true;

            if ($mask & IMP_Ftree_Account::DELETE_RECURSIVE) {
                foreach (array_map('strval', iterator_to_array(new IMP_Ftree_Iterator($elt), false)) as $val) {
                    unset(
                        $this->_elts[$val],
                        $this->_parent[$val]
                    );
                    $this->eltdiff->delete($val);
                }
                unset($this->_parent[strval($elt)]);
            }

            if (strval($account) == strval($elt)) {
                unset($this->_accounts[strval($elt)]);
            }

            if ($mask & IMP_Ftree_Account::DELETE_ELEMENT) {
                /* Do not delete from tree if there are child elements -
                 * instead, convert to a container element. */
                if ($elt->children) {
                    $elt->container = true;
                    continue;
                }

                /* Remove the mailbox from the expanded folders list. */
                unset($this->expanded[$elt]);

                /* Remove the mailbox from the polled list. */
                $this->poll->removePollList($elt);
            }

            $parent = strval($elt->parent);
            $this->eltdiff->delete($elt);

            /* Delete the entry from the parent tree. */
            unset(
                $this->_elts[strval($elt)],
                $this->_parent[$parent][array_search(strval($elt), $this->_parent[$parent], true)]
            );

            if (empty($this->_parent[$parent])) {
                /* This mailbox is now completely empty (no children). */
                unset($this->_parent[$parent]);
                if ($p_elt = $this[$parent]) {
                    if ($p_elt->container && !$p_elt->namespace) {
                        $this->delete($p_elt);
                    } else {
                        $p_elt->open = false;
                        $this->eltdiff->change($p_elt);
                    }
                }
            }

            if (!empty($this->_parent[$parent])) {
                $this->_parent[$parent] = array_values($this->_parent[$parent]);
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
        if (!($old_elt = $this[$old])) {
            return;
        }

        $new_list = $polled = array();
        $old_list = array_merge(
            array($old),
            iterator_to_array(new IMP_Ftree_IteratorFilter(new IMP_Ftree_Iterator($old_elt)), false)
        );

        foreach ($old_list as $val) {
            $new_list[] = $new_name = substr_replace($val, $new, 0, strlen($old));
            if ($val->polled) {
                $polled[] = $new_name;
            }
        }

        $this->insert($new_list);
        $this->poll->addPollList($polled);
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
            $this->sortList($id);
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
                if ($elt->children) {
                    $this->setAttribute('container', $elt, true);
                }

                /* Set as unsubscribed, add to unsubscribed list, and remove
                 * from subscribed list. */
                $this->setAttribute('subscribed', $elt, false);
            }
        }
    }

    /**
     * Load unsubscribed mailboxes.
     */
    public function loadUnsubscribed()
    {
        /* If we are switching from unsubscribed to subscribed, no need
         * to do anything (we just ignore unsubscribed stuff). */
        if ($this->unsubscribed_loaded) {
            return;
        }

        $this->_changed = true;

        /* The BASE_ELT having the SUBSCRIBED mask indicates the unsubscribed
         * mailboxes have been loaded into the object. */
        $this->setAttribute('subscribed', self::BASE_ELT, true);

        /* If we are switching from subscribed to unsubscribed, we need
         * to add all unsubscribed elements that live in currently
         * discovered items. */
        $old_track = $this->eltdiff->track;
        $this->eltdiff->track = false;
        foreach ($this->_accounts as $val) {
            array_map(array($this, '_insertElt'), $val->getList(null, $val::UNSUB));
        }
        $this->eltdiff->track = $old_track;
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
            return isset($this->_parent[$s_elt]);

        case 'container':
            $attr = self::ELT_NOSELECT;
            break;

        case 'invisible':
            $attr = self::ELT_INVISIBLE;
            break;

        case 'namespace_other':
            $attr = self::ELT_NAMESPACE_OTHER;
            break;

        case 'namespace_shared':
            $attr = self::ELT_NAMESPACE_SHARED;
            break;

        case 'needsort':
            $attr = self::ELT_NEED_SORT;
            break;

        case 'nochildren':
            $attr = self::ELT_NOINFERIORS;
            break;

        case 'nonimap':
            $attr = self::ELT_NONIMAP;
            break;

        case 'open':
            if (!$elt->children) {
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

            $polled = $this->poll[$elt];
            $this->setAttribute('polled', $elt, $polled);
            return $polled;

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

        return (bool)($this->_elts[$s_elt] & $attr);
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
            $this->eltdiff->change($elt);
            break;

        case 'invisible':
            $attr = self::ELT_INVISIBLE;
            $this->eltdiff->change($elt);
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
                $attr = self::ELT_IS_POLLED;
                $remove = self::ELT_NOT_POLLED;
            } else {
                $attr = self::ELT_NOT_POLLED;
                $remove = self::ELT_IS_POLLED;
            }
            $this->_elts[$s_elt] &= ~$remove;
            break;

        case 'subscribed':
            $attr = self::ELT_IS_SUBSCRIBED;
            $this->eltdiff->change($elt);
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
     * @return IMP_Ftree_Account  Account object.
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
     * Return the list of children for a given element ID.
     *
     * @param string $id  Element ID.
     *
     * @return array  Array of tree elements.
     */
    public function getChildren($id)
    {
        if (!($elt = $this[$id]) || !isset($this->_parent[strval($elt)])) {
            return array();
        }

        $this->_sortLevel($elt);
        return array_map(
            array($this, 'offsetGet'), $this->_parent[strval($elt)]
        );
    }

    /**
     * Get the parent element for a given element ID.
     *
     * @param string $id  Element ID.
     *
     * @return mixed  IMP_Ftree_Element object, or null if no parent.
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
     * Sorts a list of mailboxes.
     *
     * @param array &$mbox             The list of mailboxes to sort.
     * @param IMP_Ftree_Element $base  The base element.
     */
    public function sortList(&$mbox, $base = false)
    {
        if (count($mbox) < 2) {
            return;
        }

        if (!$base || (!$base->base_elt && !$base->remote_auth)) {
            $list_ob = new Horde_Imap_Client_Mailbox_List($mbox);
            $mbox = $list_ob->sort();
            return;
        }

        $prefix = $base->base_elt
            ? ''
            : (strval($this->getAccount($base)) . "\0");

        $basesort = $othersort = array();
        /* INBOX always appears first. */
        $sorted = array($prefix . 'INBOX');

        foreach ($mbox as $key => $val) {
            $ob = $this[$val];
            if ($ob->nonimap) {
                $othersort[$key] = $ob->mbox_ob->label;
            } elseif ($val !== ($prefix . 'INBOX')) {
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


    /* Internal methods. */

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

        return (strcasecmp($id, 'INBOX') === 0)
            ? 'INBOX'
            : $id;
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

        $change = false;
        if (isset($this->_elts[$name])) {
            if ($elt['a'] && self::ELT_NOSELECT) {
                return;
            }
            $change = true;
        }

        $p_elt = $this[isset($elt['p']) ? $elt['p'] : self::BASE_ELT];
        $parent = strval($p_elt);

        $this->_changed = true;

        if (!isset($this->_parent[$parent])) {
            $this->eltdiff->change($p_elt);
        }
        $this->_parent[$parent][] = $name;
        $this->_elts[$name] = $elt['a'];

        if ($change) {
            $this->eltdiff->change($name);
        } else {
            $this->eltdiff->add($name);
        }

        /* Check for polled status. */
        $this->setAttribute('polled', $name, $this->poll[$name]);

        /* Check for expanded status. */
        $this->setAttribute('open', $name, $this->expanded[$name]);

        if (empty($this->_temp['nohook'])) {
            try {
                $this->setAttribute(
                    'invisible',
                    $name,
                    !$GLOBALS['injector']->getInstance('Horde_Core_Hooks')->callHook(
                        'display_folder',
                        'imp',
                        array($name)
                    )
                );
            } catch (Horde_Exception_HookNotSet $e) {
                $this->_temp['nohook'] = true;
            }
        }

        /* Make sure we are sorted correctly. */
        $this->setAttribute('needsort', $p_elt, true);
    }

    /**
     * Sort a level in the tree.
     *
     * @param string $id  The parent element whose children need to be sorted.
     */
    protected function _sortLevel($id)
    {
        if (($elt = $this[$id]) && $elt->needsort) {
            if (count($this->_parent[strval($elt)]) > 1) {
                $this->sortList($this->_parent[strval($elt)], $elt);
            }
            $this->setAttribute('needsort', $elt, false);
        }
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset)
    {
        /* Optimization: Only normalize in the rare case it is not found on
         * the first attempt. */
        $offset = strval($offset);
        return (isset($this->_elts[$offset]) ||
                isset($this->_elts[$this->_normalize($offset)]));
    }

    /**
     * @return IMP_Ftree_Element
     */
    public function offsetGet($offset)
    {
        if ($offset instanceof IMP_Ftree_Element) {
            return $offset;
        }

        /* Optimization: Only normalize in the rare case it is not found on
         * the first attempt. */
        $offset = strval($offset);
        if (isset($this->_elts[$offset])) {
            return new IMP_Ftree_Element($offset, $this);
        }

        $offset = $this->_normalize($offset);
        return isset($this->_elts[$offset])
            ? new IMP_Ftree_Element($offset, $this)
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
        $this->loadUnsubscribed();

        $iterator = new IMP_Ftree_IteratorFilter($this);
        $iterator->add($iterator::NONIMAP);
        $iterator->remove($iterator::UNSUB);

        return iterator_count($iterator);
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return $GLOBALS['injector']->getInstance('Horde_Pack')->pack(array(
            $this->_accounts,
            $this->_eltdiff,
            $this->_elts,
            $this->_parent
        ), array(
            'compress' => false,
            'phpob' => true
        ));
    }

    /**
     * @throws Horde_Pack_Exception
     */
    public function unserialize($data)
    {
        list(
            $this->_accounts,
            $this->_eltdiff,
            $this->_elts,
            $this->_parent
        ) = $GLOBALS['injector']->getInstance('Horde_Pack')->unpack($data);
    }

    /**
     * Creates a Horde_Tree representation of the current tree.
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
     *   - iterator: (Iterator) Tree iterator to use.
     *               DEFAULT: Base iterator.
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

        $iterator = empty($opts['iterator'])
            ? new IMP_Ftree_IteratorFilter($this)
            : $opts['iterator'];

        foreach ($iterator as $val) {
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
                $label = htmlspecialchars(Horde_String::abbreviate($mbox_ob->abbrev_label, 30 - ($val->level * 2)));
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

                if (!$val->subscribed) {
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

    /* IteratorAggregate methods. */

    /**
     * This returns a RecursiveIterator - a RecursiveIteratorIterator is
     * needed to properly iterate through all elements.
     *
     * @return IMP_Ftree_Iterator  Iterator object.
     */
    public function getIterator()
    {
        return new IMP_Ftree_Iterator($this[self::BASE_ELT]);
    }

}
