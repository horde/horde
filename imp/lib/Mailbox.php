<?php
/**
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This object is a clearinghouse for actions related to an IMP mailbox.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read string $abbrev_label  Abbreviated version of $label -
 *                                      displays only the bare mailbox name
 *                                      (no parents).
 * @property-read boolean $access_creatembox  Can sub mailboxes be created?
 * @property-read boolean $access_deletembox  Can this mailbox be deleted?
 * @property-read boolean $access_deletembox_acl  Can this mailbox be deleted
 *                                                according to ACL rules?
 * @property-read boolean $access_deletemsgs  Can messages be deleted in this
 *                                            mailbox?
 * @property-read boolean $access_empty  Can this mailbox be emptied?
 * @property-read boolean $access_expunge  Can messages be expunged in this
 *                                    mailbox?
 * @property-read boolean $access_filters  Is filtering available?
 * @property-read boolean $access_flags  Are flags available?
 * @property-read boolean $access_search  Is searching available?
 * @property-read boolean $access_sort  Is sorting available?
 * @property-read boolean $access_sortthread  Is thread sort available?
 * @property-read mixed $acl  Either an ACL object for the mailbox, or null if
 *                            no ACL found for the mailbox.
 * @property-read string $basename  The basename of the mailbox (UTF-8).
 * @property array $cache  Get the cached information for this object.
 * @property-read string $cacheid  Cache ID for the mailbox.
 * @property-read string $cacheid_date  Cache ID for the mailbox, with added
 *                                      data information.
 * @property-read integer $changed  Has this object changed?
 * @property-read boolean $children  Does the element have children?
 * @property-read boolean $container  Is this a container element?
 * @property string $display  Display version of mailbox. Special mailboxes
 *                            are replaced with localized strings and
 *                            namespace information is removed.
 * @property-read string $display_html  $display that has been HTML encoded.
 * @property-read boolean $drafts  Is this a Drafts mailbox?
 * @property-read boolean $editquery  Can this search query be edited?
 * @property-read boolean $editvfolder  Can this virtual folder be edited?
 * @property-read boolean $exists  Does this mailbox exist on the IMAP server?
 * @property-read string $form_to  Converts this mailbox to a form
 *                                 representation.
 * @property-read object $icon  Icon information for the mailbox. Properties:
 *   - alt: (string) The alt text for the icon.
 *   - class: (string) The CSS class name.
 *   - icon: (Horde_Themes_Image) The icon graphic to use.
 *   - iconopen: (Horde_Themes_Image) The openicon to use.
 *   - user_icon: (boolean) Use a user defined icon?
 * @property-read IMP_Imap $imp_imap  The IMP_Imap object for this mailbox.
 * @property-read Horde_Imap_Client_Mailbox $imap_mbox_ob  Convert this object
 *                                                         tp an
 *                                                         Imap_Client mailbox
 *                                                         object.
 * @property-read boolean $inbox  Is this the INBOX?
 * @property-read boolean $innocent_show  Show the innocent action in this
 *                                        mailbox?
 * @property-read boolean $invisible  Is this mailbox invisible?
 * @property-read boolean $is_imap  Is this an IMAP mailbox?
 * @property-read boolean $is_open  Is this level expanded?
 * @property-read string $label  The mailbox label. Essentially is $display
 *                               that can be modified by user hook.
 * @property-read integer $level  The child level of this element.
 * @property-read IMP_Mailbox_List $list_ob  Returns the List object for the
 *                                           mailbox.
 * @property-read string $namespace  Is this a namespace element?
 * @property-read IMP_Mailbox $namespace_append  The mailbox with necessary
 *                                               namespace information appended.
 * @property-read string $namespace_delimiter  The delimiter for this
 *                                             namespace.
 * @property-read array $namespace_info  See IMP_Imap::getNamespace().
 * @property-read boolean $nonimap  Is this a non-IMAP element?
 * @property-read IMP_Mailbox $parent  The parent element. Returns null if no
 *                                     parent. (Base of tree is returned as
 *                                     a special element).
 * @property-read string $parent_imap  The IMAP parent name.
 * @property-read IMP_Imap_PermanentFlags $permflags  Return the list of
 *                                                    permanent flags
 *                                                    available to set in the
 *                                                    mailbox.
 * @property-read boolean $polled  Show polled information?
 * @property-read object $poll_info  Poll information for the mailbox.
 *                                   Properties:
 *   - msgs: (integer) The number of total messages in the element, if polled.
 *   - recent: (integer) The number of new messages in the element, if polled.
 *   - unseen: (integer) The number of unseen messages in the element, if
 *             polled.
 * @property-read string $pref_from  Convert mailbox name from preference
 *                                   storage.
 * @property-read string $pref_to  Convert mailbox name to preference storage.
 * @property-read boolean $query  Is this a search query?
 * @property-read boolean $readonly  Is this mailbox read-only?
 * @property-read boolean $remote  Is this a remote element?
 * @property-read IMP_Remote_Account $remote_account  Return the account
 *                                                    object for this element
 *                                                    (null if not a remote
 *                                                    element).
 * @property-read boolean $remote_container  Is this mailbox a remote special
 *                                           element?
 * @property-read boolean $remote_mbox  Is this mailbox on a remote server?
 * @property-read boolean $search  Is this a search mailbox?
 * @property-read IMP_Prefs_Sort $sortob  Sort ob for use with this mailbox.
 * @property-read boolean $spam  Is this a Spam mailbox?
 * @property-read boolean $spam_show  Show the spam action in this mailbox?
 * @property-read boolean $special  Is this is a "special" element?
 * @property-read boolean $special_outgoing  Is this a "special" element
 *                                           dealing with outgoing messages?
 * @property-read boolean $specialvfolder  Is this a "special" virtual folder?
 * @property-read boolean $sub  Is this mailbox subscribed to?
 * @property-read array $subfolders  Returns the list of subfolders as mailbox
 *                                   objects (including the current mailbox).
 * @property-read array $subfolders_only  Returns the list of subfolders as
 *                                        mailbox objects (NOT including the
 *                                        current mailbox).
 * @property-read boolean $systemquery  Is this a system (built-in) search
 *                                      query?
 * @property-read boolean $templates  Is this a Templates mailbox?
 * @property-read boolean $trash  Is this a Trash mailbox?
 * @property-read IMP_Ftree_Element $tree_elt  The tree element (null if it
 *                                             doesn't exist in the tree).
 * @property-read string $uidvalid  Returns the UIDVALIDITY string. Throws an
 *                                  IMP_Exception on error.
 * @property-read string $utf7imap  The UTF7-IMAP representation of this
 *                                  object.
 * @property-read string $value  The value of this element (IMAP mailbox name;
 *                               UTF-8).
 * @property-read boolean $vfolder  Is this a virtual folder?
 * @property-read boolean $vfolder_container  Is this the virtual folder
 *                                            container?
 * @property-read boolean $vinbox  Is this the virtual inbox?
 * @property-read boolean $vtrash  Is this the virtual trash?
 */
class IMP_Mailbox implements Serializable
{
    /* Changed constants. */
    const CHANGED_NO = 0;
    const CHANGED_YES = 1;
    const CHANGED_DELETE = 2;

    /* Special mailbox prefs. */
    const MBOX_DRAFTS = 'drafts_folder';
    const MBOX_SENT = 'sent_mail_folder';
    const MBOX_SPAM = 'spam_folder';
    const MBOX_TEMPLATES = 'composetemplates_mbox';
    const MBOX_TRASH = 'trash_folder';
    // This is just a placeholder - this pref doesn't exist.
    const MBOX_USERSPECIAL = 'user_special';

    /* Special mailbox identifiers. */
    const SPECIAL_COMPOSETEMPLATES = 'composetemplates';
    const SPECIAL_DRAFTS = 'drafts';
    const SPECIAL_SENT = 'sent';
    const SPECIAL_SPAM = 'spam';
    const SPECIAL_TRASH = 'trash';
    const SPECIAL_USER = 'userspecial';

    /* Cache identifiers. */
    // (array) ACL rights
    const CACHE_ACL = 'a';
    // (string) Display string
    const CACHE_DISPLAY = 'd';
    // (array) Icons array
    const CACHE_ICONS = 'i';
    // (string) Label string
    const CACHE_LABEL = 'l';
    // (array) Namespace information
    const CACHE_NAMESPACE = 'n';
    // (integer) UIDVALIDITY
    const CACHE_UIDVALIDITY = 'v';

    /* Cache identifiers - temporary data. */
    const CACHE_HASACLHOOK = 'ah';
    const CACHE_HASICONHOOK = 'ih';
    const CACHE_HASLABELHOOK = 'lh';
    const CACHE_ICONHOOK = 'ic';
    const CACHE_NSDEFAULT = 'nd';
    const CACHE_NSEMPTY = 'ne';
    const CACHE_PREFTO = 'pt';
    const CACHE_SPECIALMBOXES = 's';

    /**
     * Cached data.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Has this object changed?
     *
     * @var integer
     */
    protected $_changed = self::CHANGED_NO;

    /**
     * The IMAP mailbox name (UTF-8).
     *
     * @var string
     */
    protected $_mbox;

    /**
     * Temporary cached data.  Used among all instances.
     *
     * @var array
     */
    static protected $_temp = array();

    /**
     * Shortcut to obtaining mailbox object(s).
     *
     * @param mixed $mbox  The full IMAP mailbox name(s).
     *
     * @return mixed  The IMP_Mailbox object(s).
     */
    static public function get($mbox)
    {
        if (is_array($mbox)) {
            return array_filter(array_map(array(__CLASS__, 'get'), $mbox));
        }

        if ($mbox instanceof IMP_Mailbox) {
            return $mbox;
        }

        try {
            return $GLOBALS['injector']
                ->getInstance('IMP_Factory_Mailbox')
                ->create(strval($mbox));
        } catch (IMP_Exception $e) {
            return null;
        }
    }

    /**
     * Shortcut to obtaining Horde_Imap_Client_Mailbox object(s).
     *
     * @param mixed $mbox  The full IMAP mailbox name(s).
     *
     * @return mixed  The Horde_Imap_Client_Mailbox object(s).
     */
    static public function getImapMboxOb($mbox)
    {
        if (is_array($mbox)) {
            return array_filter(array_map(array(__CLASS__, 'getImapMboxOb'), $mbox));
        }

        if ($mbox instanceof Horde_Imap_Client_Mailbox) {
            return $mbox;
        }

        // Mailbox names are always UTF-8 within IMP.
        return Horde_Imap_Client_Mailbox::get(strval(
            $GLOBALS['injector']->getInstance('IMP_Remote')->getMailboxById($mbox) ?: $mbox
        ));
    }

    /**
     * Shortcut to obtaining a mailbox object from a preference name.
     *
     * @var string $pref  The preference name.
     *
     * @return IMP_Mailbox  The IMP_Mailbox object.
     */
    static public function getPref($pref)
    {
        return self::get(self::prefFrom($GLOBALS['prefs']->getValue($pref)));
    }

    /**
     * Constructor.
     *
     * @var string $mbox  The full IMAP mailbox name.
     *
     * @throws IMP_Exception
     */
    public function __construct($mbox)
    {
        if (strlen($mbox) === 0) {
            throw new IMP_Exception('Mailbox name must not be empty.');
        }

        $this->_mbox = ($mbox == IMP_Ftree::BASE_ELT)
            ? ''
            : $mbox;

        if (!isset(self::$_temp[self::CACHE_HASACLHOOK])) {
            self::$_temp[self::CACHE_HASACLHOOK] = Horde::hookExists('mbox_acl', 'imp');
            self::$_temp[self::CACHE_HASICONHOOK] = Horde::hookExists('mbox_icons', 'imp');
            self::$_temp[self::CACHE_HASLABELHOOK] = Horde::hookExists('mbox_label', 'imp');
        }
    }

    /**
     */
    public function __toString()
    {
        return strval($this->_mbox);
    }

    /**
     */
    public function __get($key)
    {
        global $injector;

        switch ($key) {
        case 'abbrev_label':
            $label = $this->label;
            return ($this->nonimap || ($pos = strrpos($label, $this->namespace_delimiter)) === false)
                ? $label
                : substr($label, $pos + 1);

        case 'access_creatembox':
            return (!($acl = $this->acl) ||
                    ($acl[Horde_Imap_Client::ACL_CREATEMBOX]));

        case 'access_deletembox':
            return ($this->access_deletembox_acl);

        case 'access_deletembox_acl':
            return (!($acl = $this->acl) ||
                    ($acl[Horde_Imap_Client::ACL_DELETEMBOX]));

        case 'access_deletemsgs':
            return (!($acl = $this->acl) ||
                    ($acl[Horde_Imap_Client::ACL_DELETEMSGS]));

        case 'access_empty':
            if ($this->access_deletemsgs && $this->access_expunge) {
                $special = $this->getSpecialMailboxes();
                return empty($special[self::SPECIAL_TRASH]) ||
                    !$special[self::SPECIAL_TRASH]->vtrash ||
                    ($special[self::SPECIAL_TRASH] == $this);
            }
            return false;

        case 'access_expunge':
            return (!($acl = $this->acl) ||
                    ($acl[Horde_Imap_Client::ACL_EXPUNGE]));

        case 'access_filters':
            return !$this->search && $this->is_imap;

        case 'access_flags':
            return $this->is_imap;

        case 'access_search':
            return $this->is_imap;

        case 'access_sort':
            /* Although possible to abstract other sorting methods, all other
             * non-sequence methods require a download of ALL messages, which
             * is too much overhead.*/
            return $this->is_imap;

        case 'access_sortthread':
            /* Thread sort is always available for IMAP servers, since
             * Horde_Imap_Client_Socket has a built-in ORDEREDSUBJECT
             * implementation. We will always prefer REFERENCES, but will
             * fallback to ORDEREDSUBJECT if the server doesn't support THREAD
             * sorting. */
            return $this->is_imap;

        case 'acl':
            if (isset($this->_cache[self::CACHE_ACL])) {
                return is_null($this->_cache[self::CACHE_ACL])
                    ? null
                    : new Horde_Imap_Client_Data_Acl($this->_cache[self::CACHE_ACL]);
            }

            $acl = $this->_cache[self::CACHE_ACL] = null;
            $this->_changed = self::CHANGED_YES;

            if (!$this->nonimap) {
                $acl = $injector->getInstance('IMP_Imap_Acl')->getACL($this, true);

                if (self::$_temp[self::CACHE_HASACLHOOK]) {
                    Horde::callHook('mbox_acl', array($this, $acl), 'imp');
                }

                /* Store string representation of ACL for a more compact
                 * serialized format. */
                $this->_cache[self::CACHE_ACL] = strval($acl);
            }

            return $acl;

        case 'basename':
            if ($this->nonimap) {
                return $this->label;
            }

            return (($pos = strrpos($this->_mbox, $this->namespace_delimiter)) === false)
                ? $this->_mbox
                : substr($this->_mbox, $pos + 1);

        case 'cache':
            return $this->_cache;

        case 'cacheid':
        case 'cacheid_date':
            return $this->_getCacheID($key == 'cacheid_date');

        case 'changed':
            return $this->_changed;

        case 'children':
            return (($elt = $this->tree_elt) && $elt->children);

        case 'container':
            return (($elt = $this->tree_elt) && $elt->container);

        case 'display':
            return $this->nonimap
                ? $this->label
                : $this->_getDisplay();

        case 'display_html':
            return htmlspecialchars($this->display);

        case 'display_notranslate':
            return $this->nonimap
                ? $this->label
                : $this->_getDisplay(true);

        case 'drafts':
            $special = $this->getSpecialMailboxes();
            return ($this->_mbox == $special[self::SPECIAL_DRAFTS]);

        case 'editquery':
            return $injector->getInstance('IMP_Search')->isQuery($this->_mbox, true);

        case 'editvfolder':
            return $injector->getInstance('IMP_Search')->isVFolder($this->_mbox, true);

        case 'exists':
            if ($this->search) {
                return ($ob = $this->getSearchOb()) && $ob->enabled;
            }

            if ($elt = $this->tree_elt) {
                return !$elt->container;
            }

            try {
                return (bool)$this->imp_imap->listMailboxes($this->imap_mbox_ob, null, array('flat' => true));
            } catch (IMP_Imap_Exception $e) {
                return false;
            }

        case 'form_to':
            return $this->formTo($this->_mbox);

        case 'icon':
            return $this->_getIcon();

        case 'imp_imap':
            return $injector->getInstance('IMP_Factory_Imap')->create($this->_mbox);

        case 'imap_mbox_ob':
            return self::getImapMboxOb($this->_mbox);

        case 'inbox':
            return (strcasecmp($this->_mbox, 'INBOX') === 0);

        case 'innocent_show':
            $p = $this->imp_imap->config->innocent_params;
            return (!empty($p) &&
                    ((isset($p['display']) && empty($p['display'])) || $this->spam));

        case 'invisible':
            return (($elt = $this->tree_elt) && $elt->invisible);

        case 'is_imap':
            return $this->imp_imap->isImap();

        case 'is_open':
            return (($elt = $this->tree_elt) && $elt->open);

        case 'label':
            if (!isset($this->_cache[self::CACHE_LABEL])) {
                /* Returns the plain text label that is displayed for the
                 * current mailbox, replacing virtual search mailboxes with an
                 * appropriate description, removing namespace and mailbox
                 * prefix information from what is shown to the user, and
                 * passing the label through a user-defined hook. */
                $imp_search = $injector->getInstance('IMP_Search');
                $label = ($ob = $imp_search[$this->_mbox])
                    ? $ob->label
                    : $this->_getDisplay();

                if (self::$_temp[self::CACHE_HASLABELHOOK]) {
                    $label = Horde::callHook('mbox_label', array($this->_mbox, $label), 'imp');
                }

                $this->_cache[self::CACHE_LABEL] = (isset($this->_cache[self::CACHE_DISPLAY]) && ($this->_cache[self::CACHE_DISPLAY] == $label))
                    ? true
                    : $label;
                $this->_changed = self::CHANGED_YES;
            }

            return ($this->_cache[self::CACHE_LABEL] === true)
                ? $this->_cache[self::CACHE_DISPLAY]
                : $this->_cache[self::CACHE_LABEL];

        case 'level':
            return ($elt = $this->tree_elt) ? $elt->level : 0;

        case 'list_ob':
            return $injector->getInstance('IMP_Factory_MailboxList')->create($this);

        case 'namespace':
            return (($elt = $this->tree_elt) && $elt->namespace);

        case 'namespace_append':
            $imp_imap = $this->imp_imap;
            $def_ns = $imp_imap->defaultNamespace();
            if (is_null($def_ns)) {
                return $this;
            }
            $empty_ns = $imp_imap->getNamespace('');

            /* If default namespace is empty, or there is no empty namespace,
             * then we can auto-detect namespace from input.
             * If a non-default namespace is empty, then we must always use
             * default namespace. */
            if (!is_null($empty_ns) &&
                ($def_ns['name'] == $empty_ns['name'])) {
                return $this;
            }

            $ns_info = $this->namespace_info;

            if (is_null($ns_info) || !is_null($empty_ns)) {
                return self::get($def_ns['name'] . $this->_mbox);
            }

            return $this;

        case 'namespace_delimiter':
            $ns_info = $this->namespace_info;
            return is_null($ns_info)
                ? ''
                : $ns_info['delimiter'];

        case 'namespace_info':
            $keys = array('delimiter', 'hidden', 'name', 'translation', 'type');

            if (isset($this->_cache[self::CACHE_NAMESPACE])) {
                $ns = $this->_cache[self::CACHE_NAMESPACE];

                if (is_null($ns)) {
                    return null;
                }

                $ret = array();
                foreach ($keys as $key => $val) {
                    $ret[$val] = isset($ns[$key])
                        ? $ns[$key]
                        : '';
                }

                return $ret;
            }

            $ns_info = $this->imp_imap->getNamespace($this->_mbox);
            if (is_null($ns_info)) {
                $this->_cache[self::CACHE_NAMESPACE] = null;
            } else {
                /* Store data compressed in the cache array. */
                $this->_cache[self::CACHE_NAMESPACE] = array();
                foreach ($keys as $id => $key) {
                    if ($ns_info[$key]) {
                        $this->_cache[self::CACHE_NAMESPACE][$id] = $ns_info[$key];
                    }
                }
            }

            $this->_changed = self::CHANGED_YES;

            return $ns_info;

        case 'nonimap':
            return ($this->search ||
                    (($elt = $this->tree_elt) && $elt->nonimap));

        case 'parent':
            return ($elt = $this->tree_elt) ? $elt->parent->mbox_ob : null;

        case 'parent_imap':
            return (is_null($p = $this->parent) || ($p == IMP_Ftree::BASE_ELT))
                ? null
                : $p;

        case 'permflags':
            if ($this->access_flags) {
                $imp_imap = $this->imp_imap;
                try {
                    /* Make sure we are in R/W mailbox mode (SELECT). No flags
                     * are allowed in EXAMINE mode. */
                    $imp_imap->openMailbox($this, Horde_Imap_Client::OPEN_READWRITE);
                    $status = $imp_imap->status($this->_mbox, Horde_Imap_Client::STATUS_FLAGS | Horde_Imap_Client::STATUS_PERMFLAGS);
                    return new IMP_Imap_PermanentFlags($status['permflags'], $status['flags']);
                } catch (Exception $e) {}
            }

            return new IMP_Imap_PermanentFlags();

        case 'poll_info':
            $info = new stdClass;
            $info->msgs = 0;
            $info->recent = 0;
            $info->unseen = 0;

            try {
                if ($msgs_info = $this->imp_imap->status($this->_mbox, Horde_Imap_Client::STATUS_RECENT_TOTAL | Horde_Imap_Client::STATUS_UNSEEN | Horde_Imap_Client::STATUS_MESSAGES)) {
                    if (!empty($msgs_info['recent_total'])) {
                        $info->recent = intval($msgs_info['recent_total']);
                    }
                    $info->msgs = intval($msgs_info['messages']);
                    $info->unseen = intval($msgs_info['unseen']);
                }
            } catch (IMP_Imap_Exception $e) {}

            return $info;

        case 'polled':
            return (!$this->search &&
                    (($elt = $this->tree_elt) && $elt->polled));

        case 'pref_from':
            return $this->prefFrom($this->_mbox);

        case 'pref_to':
            if (!isset(self::$_temp[self::CACHE_PREFTO])) {
                self::$_temp[self::CACHE_PREFTO] = $this->prefTo($this->_mbox);
            }
            return self::$_temp[self::CACHE_PREFTO];

        case 'query':
            return $injector->getInstance('IMP_Search')->isQuery($this->_mbox);

        case 'readonly':
            return (($acl = $this->acl) &&
                    !$acl[Horde_Imap_Client::ACL_DELETEMBOX] &&
                    !$acl[Horde_Imap_Client::ACL_DELETEMSGS] &&
                    !$acl[Horde_Imap_Client::ACL_EXPUNGE] &&
                    !$acl[Horde_Imap_Client::ACL_INSERT] &&
                    !$acl[Horde_Imap_Client::ACL_SEEN] &&
                    !$acl[Horde_Imap_Client::ACL_WRITE]);

        case 'remote':
            return $injector->getInstance('IMP_Remote')->isRemoteMbox($this->_mbox);

        case 'remote_account':
            $remote = $injector->getInstance('IMP_Remote');
            $account = ($this->remote_container)
                ? $remote[$this->_mbox]
                : $remote->getRemoteById($this->_mbox);
            return $account ?: null;

        case 'remote_container':
            return (($elt = $this->tree_elt) && $elt->remote);

        case 'remote_mbox':
            return (($elt = $this->tree_elt) && $elt->remote_mbox);

        case 'search':
            return $injector->getInstance('IMP_Search')->isSearchMbox($this->_mbox);

        case 'sortob':
            return $this->imp_imap->canSort($this)
                ? $injector->getInstance('IMP_Prefs_Sort')
                : $injector->getInstance('IMP_Prefs_Sort_None');

        case 'spam':
            $special = $this->getSpecialMailboxes();
            return ($this->_mbox == $special[self::SPECIAL_SPAM]);

        case 'spam_show':
            $p = $this->imp_imap->config->spam_params;
            return (!empty($p) && (!empty($p['display']) || !$this->spam));

        case 'special':
            $special = $this->getSpecialMailboxes();

            switch ($this->_mbox) {
            case $special[self::SPECIAL_COMPOSETEMPLATES]:
            case $special[self::SPECIAL_DRAFTS]:
            case $special[self::SPECIAL_SPAM]:
            case $special[self::SPECIAL_TRASH]:
                return true;
            }

            return in_array($this->_mbox, array_merge(
                $special[self::SPECIAL_SENT],
                $special[self::SPECIAL_USER]
            ));

        case 'special_outgoing':
            $special = $this->getSpecialMailboxes();

            return in_array($this->_mbox, array_merge(
                array(
                    $special[self::SPECIAL_COMPOSETEMPLATES],
                    $special[self::SPECIAL_DRAFTS]
                ),
                $special[self::SPECIAL_SENT]
            ));

        case 'specialvfolder':
            return !$this->editvfolder;

        case 'sub':
            return (($elt = $this->tree_elt) && $elt->subscribed);

        case 'subfolders':
            return $this->get(array_merge(array($this->_mbox), $this->subfolders_only));

        case 'subfolders_only':
            return $this->get($this->imp_imap->listMailboxes($this->imap_mbox_ob->list_escape . $this->namespace_delimiter . '*', null, array('flat' => true)));

        case 'systemquery':
            return $injector->getInstance('IMP_Search')->isSystemQuery($this->_mbox);

        case 'templates':
            $special = $this->getSpecialMailboxes();
            return ($this->_mbox == $special[self::SPECIAL_COMPOSETEMPLATES]);

        case 'trash':
            $special = $this->getSpecialMailboxes();
            return ($this->_mbox == $special[self::SPECIAL_TRASH]);

        case 'tree_elt':
            $ftree = $injector->getInstance('IMP_Ftree');
            return $ftree[$this->_mbox];

        case 'uidvalid':
            // POP3 and non-IMAP mailboxes does not support UIDVALIDITY.
            if (!$this->is_imap || $this->nonimap) {
                return;
            }

            $status = $this->imp_imap->status($this->_mbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
            if (($first = !isset($this->_cache[self::CACHE_UIDVALIDITY])) ||
                ($status['uidvalidity'] != $this->_cache[self::CACHE_UIDVALIDITY])) {
                $this->_cache[self::CACHE_UIDVALIDITY] = $status['uidvalidity'];
                $this->_changed = self::CHANGED_YES;

                if (!$first) {
                    throw new IMP_Exception(_("Mailbox structure on server has changed."));
                }
            }

            return $this->_cache[self::CACHE_UIDVALIDITY];

        case 'utf7imap':
            return Horde_String::convertCharset($this->_mbox, 'UTF-8', 'UTF7-IMAP');

        case 'value':
            return $this->_mbox;

        case 'vfolder':
            return $injector->getInstance('IMP_Search')->isVFolder($this->_mbox);

        case 'vfolder_container':
            return ($this->_mbox == IMP_Ftree::VFOLDER_KEY);

        case 'vinbox':
            return $injector->getInstance('IMP_Search')->isVinbox($this->_mbox);

        case 'vtrash':
            return $injector->getInstance('IMP_Search')->isVTrash($this->_mbox);
        }

        return false;
    }

    /**
     */
    public function __set($key, $value)
    {
        switch ($key) {
        case 'cache':
            $this->_cache = $value;
            break;

        case 'display':
            $this->_cache[self::CACHE_DISPLAY] = $value;
            break;
        }
    }

    /**
     * Create this mailbox on the server.
     *
     * @param array $opts  Additional options:
     *   - special_use: (array) An array of special-use attributes to attempt
     *                  to add to the mailbox.
     *                  DEFAULT: NONE
     *   - subscribe: (boolean) Override preference value of subscribe.
     *
     * @return boolean  True on success.
     * @throws Horde_Exception
     */
    public function create(array $opts = array())
    {
        global $injector, $notification, $prefs;

        if ($this->exists) {
            return true;
        }

        $imp_imap = $this->imp_imap;

        /* Check permissions. */
        if (!$imp_imap->access(IMP_Imap::ACCESS_CREATEMBOX)) {
            Horde::permissionDeniedError(
                'imp',
                'create_mboxes',
                _("You are not allowed to create mailboxes.")
            );
            return false;
        }
        if (!$imp_imap->access(IMP_Imap::ACCESS_CREATEMBOX_MAX)) {
            Horde::permissionDeniedError(
                'imp',
                'max_create_mboxes',
                sprintf(_("You are not allowed to create more than %d mailboxes."), $imp_imap->max_create_mboxes)
            );
            return false;
        }

        /* Special use flags. */
        $special_use = isset($opts['special_use'])
            ? $opts['special_use']
            : array();

        /* Attempt to create the mailbox. */
        try {
            $imp_imap->createMailbox($this->_mbox, array('special_use' => $special_use));
        } catch (IMP_Imap_Exception $e) {
            if ($e->getCode() == $e::USEATTR) {
                unset($opts['special_use']);
                return $this->create($opts);
            }

            $e->notify(sprintf(_("The mailbox \"%s\" was not created. This is what the server said"), $this->display) . ': ' . $e->getMessage());
            return false;
        }

        $notification->push(sprintf(_("The mailbox \"%s\" was successfully created."), $this->display), 'horde.success');

        /* Subscribe, if requested. */
        if ((!isset($opts['subscribe']) && $prefs->getValue('subscribe')) ||
            !empty($opts['subscribe'])) {
            $this->subscribe(true);
        }

        /* Update the mailbox tree. */
        $injector->getInstance('IMP_Ftree')->insert($this->_mbox);

        return true;
    }

    /**
     * Deletes mailbox.
     *
     * @param array $opts  Addtional options:
     *   - subfolders: (boolean) Delete all subfolders?
     *     DEFAULT: false
     *   - subfolders_only: (boolean) If deleting subfolders, delete only
     *                      subfolders (not current mailbox)?
     *     DEFAULT: false
     *
     * @return boolean  True on success.
     */
    public function delete(array $opts = array())
    {
        global $injector, $notification;

        if ($this->vfolder) {
            if ($this->editvfolder) {
                $imp_search = $injector->getInstance('IMP_Search');
                $label = $imp_search[$this->_mbox]->label;
                unset($imp_search[$this->_mbox]);
                $notification->push(sprintf(_("Deleted Virtual Folder \"%s\"."), $label), 'horde.success');
                return true;
            }

            $notification->push(sprintf(_("Could not delete Virtual Folder \"%s\"."), $this->label), 'horde.error');
            return false;
        }

        $deleted = array();
        $imp_imap = $this->imp_imap;
        if (empty($opts['subfolders'])) {
            $to_delete = array($this);
        } else {
            $to_delete = empty($opts['subfolders_only'])
                ? $this->subfolders
                : $this->subfolders_only;
        }

        foreach ($to_delete as $val) {
            if (!$val->access_deletembox_acl) {
                $notification->push(sprintf(_("The mailbox \"%s\" may not be deleted."), $val->display), 'horde.error');
                continue;
            }

            try {
                $imp_imap->deleteMailbox($val->value);
                $notification->push(sprintf(_("The mailbox \"%s\" was successfully deleted."), $val->display), 'horde.success');
                $deleted[] = $val;
            } catch (IMP_Imap_Exception $e) {
                $e->notify(sprintf(_("The mailbox \"%s\" was not deleted. This is what the server said"), $val->display) . ': ' . $e->getMessage());
            }
        }

        if (!empty($deleted)) {
            $injector->getInstance('IMP_Ftree')->delete($deleted);
            $this->_onDelete($deleted);
        }

        return (count($deleted) == count($to_delete));
    }

    /**
     * Rename this mailbox on the server. The subscription status remains the
     * same.  All subfolders will also be renamed.
     *
     * @param string $new_name  The new mailbox name (UTF-8).
     *
     * @return boolean  True on success
     */
    public function rename($new_name)
    {
        global $injector, $notification;

        /* Don't try to rename to an empty string. */
        if (!strlen($new_name)) {
            return false;
        }

        if (!$this->access_deletembox_acl) {
            $notification->push(sprintf(_("The mailbox \"%s\" may not be renamed."), $this->display), 'horde.error');
            return false;
        }

        $new_mbox = $this->get($new_name);

        /* TODO(?): Don't allow moving mailboxes between accounts. */
        if ($this->remote_account != $new_mbox->remote_account) {
            $notification->push(_("You cannot move mailboxes between accounts."), 'horde.error');
            return false;
        }

        $old_list = $this->subfolders;

        try {
            $this->imp_imap->renameMailbox($this->_mbox, $new_mbox);
        } catch (IMP_Imap_Exception $e) {
            $e->notify(sprintf(_("Renaming \"%s\" to \"%s\" failed. This is what the server said"), $this->display, $new_mbox->display) . ': ' . $e->getMessage());
            return false;
        }

        $notification->push(sprintf(_("The mailbox \"%s\" was successfully renamed to \"%s\"."), $this->display, $new_mbox->display), 'horde.success');

        $injector->getInstance('IMP_Ftree')->rename($this->_mbox, $new_mbox);
        $this->_onDelete($old_list);

        return true;
    }

    /**
     * Subscribe/unsubscribe to an IMAP mailbox.
     *
     * @param boolean $sub  True to subscribe, false to unsubscribe.
     * @param array $opts   Additional options:
     * <pre>
     *   - subfolders: (boolean) If true, applies actions to all subfolders.
     * </pre>
     *
     * @return boolean  True on success.
     */
    public function subscribe($sub, array $opts = array())
    {
        global $injector, $notification, $prefs;

        /* Skip non-IMAP/container mailboxes. */
        if (!$prefs->getValue('subscribe') ||
            $this->nonimap ||
            $this->container) {
            return false;
        }

        if (!$sub && $this->inbox) {
            $notification->push(sprintf(_("You cannot unsubscribe from \"%s\"."), $this->display), 'horde.error');
            return false;
        }

        $imp_imap = $this->imp_imap;

        try {
            $imp_imap->subscribeMailbox($this->_mbox, $sub);
        } catch (IMP_Imap_Exception $e) {
            if ($sub) {
                $e->notify(sprintf(_("You were not subscribed to \"%s\". Here is what the server said"), $this->display) . ': ' . $e->getMessage());
            } else {
                $e->notify(sprintf(_("You were not unsubscribed from \"%s\". Here is what the server said"), $this->display) . ': ' . $e->getMessage());
            }
            return false;
        }

        $imap_tree = $injector->getInstance('IMP_Ftree');
        if ($sub) {
            $imap_tree->subscribe($this->_mbox);
        } else {
            $imap_tree->unsubscribe($this->_mbox);
        }

        if (empty($opts['subfolders'])) {
            $notify = $sub
                ? sprintf(_("You were successfully subscribed to \"%s\"."), $this->display)
                : sprintf(_("You were successfully unsubscribed from \"%s\"."), $this->display);
        } else {
            $action = false;

            foreach ($this->subfolders_only as $val) {
                try {
                    $imp_imap->subscribeMailbox($val, $sub);
                    if ($sub) {
                        $imap_tree->subscribe($val);
                    } else {
                        $imap_tree->unsubscribe($val);
                    }

                    $action = true;
                } catch (IMP_Imap_Exception $e) {
                    // Ignore errors for sub-mailboxes.
                }
            }

            if ($action) {
                $notify = $sub
                    ? sprintf(_("You were successfully subscribed to \"%s\" and all subfolders."), $this->display)
                    : sprintf(_("You were successfully unsubscribed from \"%s\" and all subfolders."), $this->display);
            }
        }

        $notification->push($notify, 'horde.success');

        return true;
    }

    /**
     * Runs filters on this mailbox.
     */
    public function filter()
    {
        if (!$this->search) {
            $GLOBALS['injector']->getInstance('IMP_Filter')->filter($this);
        }
    }

    /**
     * Filters this mailbox if it is the INBOX and the filter on display pref
     * is active.
     *
     * @return boolean  True if filter() was called.
     */
    public function filterOnDisplay()
    {
        if ($this->inbox &&
            $GLOBALS['prefs']->getValue('filter_on_display')) {
            $this->filter();
            return true;
        }

        return false;
    }

    /**
     * Return the search query object for this mailbox.
     *
     * @return IMP_Search_Query  The search query object.
     */
    public function getSearchOb()
    {
        $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
        return $imp_search[$this->_mbox];
    }

    /**
     * Return an indices object for this mailbox.
     *
     * @param mixed $in  Either a single UID, array of UIDs, or a
     *                   Horde_Imap_Client_Ids object.
     *
     * @return IMP_Indices  An indices object.
     */
    public function getIndicesOb($in)
    {
        return new IMP_Indices($this, $in);
    }

    /**
     * Return the sorting preference for this mailbox.
     *
     * @param boolean $convert  Convert 'by' to a Horde_Imap_Client constant?
     *
     * @return IMP_Prefs_Sort_Sortpref  Sortpref object.
     */
    public function getSort($convert = false)
    {
        global $prefs;

        $mbox = $this->search
            ? $this
            : self::get($this->pref_from);

        $ob = $this->sortob[strval($mbox)];
        $ob->convertSortby();

        if ($convert && ($ob->sortby == IMP::IMAP_SORT_DATE)) {
            $ob->sortby = $prefs->getValue('sortdate');
        }

        return $ob;
    }

    /**
     * Set the sorting preference for this mailbox.
     *
     * @param integer $by      The sort type.
     * @param integer $dir     The sort direction.
     * @param boolean $delete  Delete the entry?
     */
    public function setSort($by = null, $dir = null, $delete = false)
    {
        $mbox = $this->search
            ? $this
            : self::get($this->pref_from);

        if ($delete) {
            unset($this->sortob[strval($mbox)]);
        } else {
            $change = array();
            if (!is_null($by)) {
                $change['by'] = $by;
            }
            if (!is_null($dir)) {
                $change['dir'] = $dir;
            }
            $this->sortob[strval($mbox)] = $change;
        }
    }

    /**
     * Are deleted messages hidden in this mailbox?
     *
     * @param boolean $deleted  Return value is what should be done with
     *                          deleted messages in general, as opposed to any
     *                          deleted message in the mailbox.
     *
     * @return boolean  True if deleted messages should be hidden.
     */
    public function hideDeletedMsgs($deleted = false)
    {
        global $prefs;

        if (!$this->access_flags) {
            return $this->is_imap;
        }

        if ($prefs->getValue('use_trash')) {
            /* If using Virtual Trash, only show deleted messages in
             * the Virtual Trash mailbox. */
            return $this->get($prefs->getValue(self::MBOX_TRASH))->vtrash
                ? !$this->vtrash
                : ($prefs->getValue('delhide_trash') ? true : $deleted);
        }

        return $prefs->getValue('delhide');
    }

    /**
     * Sets the 'delhide' preference and clears necessary cached data.
     *
     * @param boolean $value  The value to set 'delhide' to.
     */
    public function setHideDeletedMsgs($value)
    {
        $GLOBALS['prefs']->setValue('delhide', $value);
        $GLOBALS['injector']->getInstance('IMP_Factory_MailboxList')->expireAll();
    }

    /**
     * Run a search query on this mailbox that is not stored in the current
     * session. Allows custom queries with custom sorts to be used without
     * affecting cached mailboxes.
     *
     * @param Horde_Imap_Client_Search_Query $query  The search query object.
     * @param integer $sortby                        The sort criteria.
     * @param integer $sortdir                       The sort directory.
     *
     * @return IMP_Indices  An indices object.
     */
    public function runSearchQuery(Horde_Imap_Client_Search_Query $query,
                                   $sortby = null, $sortdir = null)
    {
        try {
            $results = $this->imp_imap->search($this, $query, array(
                'sort' => is_null($sortby) ? null : array($sortby)
            ));
            if ($sortdir) {
                $results['match']->reverse();
            }
            return $this->getIndicesOb($results['match']);
        } catch (IMP_Imap_Exception $e) {
            return new IMP_Indices();
        }
    }

    /**
     * Expire cache entries.
     *
     * @param mixed $entries  A CACHE_* constant (or array of constants). If
     *                        null, clears the entire cache.
     */
    public function expire($entries = null)
    {
        if (is_null($entries)) {
            $changed = true;
            $this->_cache = array();
        } else {
            $changed = false;

            if (!is_array($entries)) {
                $entries = array($entries);
            }

            foreach ($entries as $val) {
                $changed = isset($this->_cache[$val]);
                unset($this->_cache[$val], self::$_temp[$val]);
            }
        }

        if ($changed) {
            $this->_changed = empty($this->_cache)
                ? self::CHANGED_DELETE
                : self::CHANGED_YES;
        }
    }

    /**
     * Generate a URL using the current mailbox.
     *
     * @param string|Horde_Url $page  Page name to link to.
     * @param string $buid            The BUID to use on the linked page.
     * @param boolean $encode         Encode the argument separator?
     *
     * @return Horde_Url  URL to $page with any necessary mailbox information
     *                    added to the parameter list of the URL.
     */
    public function url($page, $buid = null, $encode = true)
    {
        if ($page instanceof Horde_Url) {
            return $page->add($this->urlParams($buid))->setRaw(!$encode);
        }

        switch ($GLOBALS['registry']->getView()) {
        case Horde_Registry::VIEW_BASIC:
            switch ($page) {
            case 'message':
                return IMP_Basic_Message::url(array(
                    'buid' => $buid,
                    'mailbox' => $this->_mbox
                ))->setRaw(!$encode);

            case 'mailbox':
                return IMP_Basic_Mailbox::url(array(
                    'mailbox' => $this->_mbox
                ))->setRaw(!$encode);
            }
            break;

        case Horde_Registry::VIEW_DYNAMIC:
            $anchor = is_null($buid)
                ? ('mbox:' . $this->form_to)
                : ('msg:' . $this->form_to . ';' . $buid);
            return Horde::url('index.php')->setAnchor($anchor);

        case Horde_Registry::VIEW_MINIMAL:
            switch ($page) {
            case 'message':
                return IMP_Minimal_Message::url(array(
                    'buid' => $buid,
                    'mailbox' => $this->_mbox
                ))->setRaw(!$encode);

            case 'mailbox':
                return IMP_Minimal_Mailbox::url(array(
                    'mailbox' => $this->_mbox
                ))->setRaw(!$encode);
            }
            break;

        case Horde_Registry::VIEW_SMARTMOBILE:
            $url = Horde::url('smartmobile.php');
            $anchor = is_null($buid)
                ? ('mbox=' . $this->form_to)
                : ('msg=' . $this->form_to . ';' . $buid);
            $url->setAnchor('mailbox?' . $anchor);
            return $url;
        }

        return Horde::url($page . '.php')->add($this->urlParams($buid))->setRaw(!$encode);
    }

    /**
     * Returns list of URL parameters necessary to indicate current mailbox
     * status.
     *
     * @param string $buid  The BUID to use on the linked page.
     *
     * @return array  The list of parameters needed to indicate the current
     *                mailbox status.
     */
    public function urlParams($buid = null)
    {
        $params = array('mailbox' => $this->form_to);
        if (!is_null($buid)) {
            $params['buid'] = $buid;
        }
        return $params;
    }

    /**
     * Determines if this mailbox is equal to the given mailbox.
     * Needed because directly comparing two mailbox objects may fail (the
     * member variables may be different).
     *
     * @param mixed $mbox  The mailbox to compare to.
     *
     * @return boolean  True if the mailboxes are the same.
     */
    public function equals($mbox)
    {
        return ($mbox == $this->_mbox);
    }

    /**
     * Create an indices object from a list of browser-UIDs.
     *
     * @param IMP_Indices|array $buids  Browser-UIDs.
     *
     * @return IMP_Indices  An indices object.
     */
    public function fromBuids($buids)
    {
        if (is_array($buids)) {
            $buids = new IMP_Indices($this->_mbox, $buids);
        }
        $buid_list = $buids->getSingle(true);

        $list_ob = $this->list_ob;
        $out = new IMP_Indices();

        foreach ($buid_list[1] as $buid) {
            if ($resolve = $list_ob->resolveBuid($buid)) {
                $out->add($resolve['m'], $resolve['u']);
            }
        }

        return $out;
    }

    /**
     * Create a BUID indices object from a list of UIDs.
     *
     * @param IMP_Indices $uids  UIDs.
     *
     * @return IMP_Indices  An indices object.
     */
    public function toBuids(IMP_Indices $uids)
    {
        $list_ob = $this->list_ob;
        $out = new IMP_Indices();

        foreach ($uids as $val) {
            foreach ($val->uids as $val2) {
                $out->add($this->_mbox, $list_ob->getBuid($val->mbox, $val2));
            }
        }

        return $out;
    }

    /**
     * Determines the mailbox name to create given a parent and the new name.
     *
     * @param string $new  The new mailbox name (UTF-8).
     *
     * @return IMP_Mailbox  The new mailbox.
     */
    public function createMailboxName($new)
    {
        if (strlen($this->_mbox)) {
            if ($this->remote_container) {
                $new = $this->remote_account->mailbox($new);
            } else {
                $ns_info = $this->namespace_info;
                $new = $this->_mbox . $ns_info['delimiter'] . $new;
            }
        }

        return self::get($new);
    }

    /* Static methods. */

    /**
     * Converts a mailbox string from a form representation.
     * Needed because null characters (used for various internal non-IMAP
     * mailbox representations) will not work in form elements.
     *
     * @param mixed $mbox  The mailbox name(s).
     *
     * @return mixed  The mailbox object(s).
     */
    static public function formFrom($mbox)
    {
        return is_array($mbox)
            ? array_filter(array_map(array(__CLASS__, 'formFrom'), $mbox))
              // Base64url (RFC 4648 [5]) encoding
            : self::get(base64_decode(strtr($mbox, '-_', '+/')));
    }

    /**
     * Converts a mailbox string to a form representation.
     * Needed because null characters (used for various internal non-IMAP
     * mailbox representations) will not work in form elements.
     *
     * @param mixed $mbox  The mailbox name(s).
     *
     * @return mixed  The converted mailbox string(s).
     */
    static public function formTo($mbox)
    {
        return is_array($mbox)
            ? array_filter(array_map(array(__CLASS__, 'formTo'), $mbox))
              // Base64url (RFC 4648 [5]) encoding
            : strtr(rtrim(base64_encode($mbox), '='), '+/', '-_');
    }

    /**
     * Return the list of special mailboxes.
     *
     * @return array  A list of mailboxes, with the self::SPECIAL_* constants
     *                as keys and values containing the IMP_Mailbox objects or
     *                null if the mailbox doesn't exist (self::SPECIAL_SENT
     *                contains an array of objects).
     */
    static public function getSpecialMailboxes()
    {
        if (!isset(self::$_temp[self::CACHE_SPECIALMBOXES])) {
            $sm = &self::$_temp[self::CACHE_SPECIALMBOXES];

            $sm = array(
                self::SPECIAL_COMPOSETEMPLATES => self::getPref(self::MBOX_TEMPLATES),
                self::SPECIAL_DRAFTS => self::getPref(self::MBOX_DRAFTS),
                self::SPECIAL_SENT => $GLOBALS['injector']->getInstance('IMP_Identity')->getAllSentmail(),
                self::SPECIAL_SPAM => self::getPref(self::MBOX_SPAM),
                self::SPECIAL_TRASH => $GLOBALS['prefs']->getValue('use_trash') ? self::getPref(self::MBOX_TRASH) : null,
                self::SPECIAL_USER => array()
            );

            foreach ($GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->config->user_special_mboxes as $key => $val) {
                $ob = self::get($key);
                $ob->display = $val;
                $sm[self::SPECIAL_USER][strval($key)] = $ob;
            }
        }

        return self::$_temp[self::CACHE_SPECIALMBOXES];
    }

    /**
     * Return the list of sorted special mailboxes.
     *
     * @return array  The list of sorted special mailboxes (IMP_Mailbox
     *                objects).
     */
    static public function getSpecialMailboxesSort()
    {
        $out = array();

        foreach (array_filter(self::getSpecialMailboxes()) as $val) {
            if (is_array($val)) {
                $tmp = array();
                foreach ($val as $val2) {
                    $tmp[strval($val2)] = $val2->abbrev_label;
                }
                asort($tmp, SORT_LOCALE_STRING);
                $out = array_merge($out, array_keys($tmp));
            } else {
                $out[] = $val;
            }
        }

        return self::get($out);
    }

    /**
     * Converts a mailbox name from a value stored in the preferences.
     *
     * @param string $mbox  The mailbox name as stored in a preference.
     *
     * @return string  The full IMAP mailbox name (UTF-8).
     */
    static public function prefFrom($mbox)
    {
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

        if (!isset(self::$_temp[self::CACHE_NSDEFAULT])) {
            self::$_temp[self::CACHE_NSDEFAULT] = $imp_imap->defaultNamespace();
            self::$_temp[self::CACHE_NSEMPTY] = $imp_imap->getNamespace('');
        }

        $empty_ns = self::$_temp[self::CACHE_NSEMPTY];

        if (!is_null($empty_ns) &&
            (strpos($mbox, $empty_ns['delimiter']) === 0)) {
            /* Prefixed with delimiter => from empty namespace. */
            return substr($mbox, strlen($empty_ns['delimiter']));
        } elseif ($imp_imap->getNamespace($mbox, true) === null) {
            /* No namespace prefix => from personal namespace. */
            return self::$_temp[self::CACHE_NSDEFAULT]['name'] . $mbox;
        }

        return $mbox;
    }

    /**
     * Converts a mailbox name to a value to be stored in a preference.
     *
     * @param string $mbox  The full IMAP mailbox name (UTF-8).
     *
     * @return string  The value to store in a preference.
     */
    static public function prefTo($mbox)
    {
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
        $def_ns = $imp_imap->defaultNamespace();
        $empty_ns = $imp_imap->getNamespace('');

        if (($ns = self::get($mbox)->namespace_info) !== null) {
            if ($ns['name'] == $def_ns['name']) {
                /* From personal namespace => strip namespace. */
                return substr($mbox, strlen($def_ns['name']));
            } elseif ($ns['name'] == $empty_ns['name']) {
                /* From empty namespace => prefix with delimiter. */
                return $empty_ns['delimiter'] . $mbox;
            }
        }

        return strval($mbox);
    }

    /* Internal methods. */

    /**
     * Returns a unique identifier for this mailbox's status.
     *
     * This cache ID is guaranteed to change if messages are added/deleted
     * from the mailbox. Additionally, if CONDSTORE is available on the remote
     * IMAP server, this ID will change if flag information changes.
     *
     * For search mailboxes, this value never changes (search mailboxes must
     * be forcibly refreshed).
     *
     * @param boolean $date  If true, adds date information to ID.
     *
     * @return string  The cache ID string, which will change when the
     *                 composition of this mailbox changes.
     */
    protected function _getCacheID($date = false)
    {
        $date = $date
            ? 'D' . date('z')
            : '';

        if ($this->search) {
            return '1' . ($date ? '|' . $date : '');
        }

        $sortpref = $this->getSort(true);
        $addl = array($sortpref->sortby, $sortpref->sortdir);
        if ($date) {
            $addl[] = $date;
        }

        try {
            return $this->imp_imap->getCacheId($this->_mbox, $addl);
        } catch (IMP_Imap_Exception $e) {
            /* Assume an error means that a mailbox can not be trusted. */
            return strval(new Horde_Support_Randomid());
        }
    }

    /**
     * If there is information available to tell us about a prefix in front of
     * mailbox names that shouldn't be displayed to the user, then use it to
     * strip that prefix out. Additionally, translate prefix text if this
     * is a special mailbox.
     *
     * @param boolean $notranslate  Don't translate the mailbox prefix?
     *
     * @return string  The mailbox, with any prefix gone/translated.
     */
    protected function _getDisplay($notranslate = false)
    {
        global $injector;

        if (!$notranslate && isset($this->_cache[self::CACHE_DISPLAY])) {
            return $this->_cache[self::CACHE_DISPLAY];
        }

        /* Handle special container mailboxes. */
        switch ($this->_mbox) {
        case IMP_Ftree::OTHER_KEY:
            return _("Other Users");

        case IMP_Ftree::REMOTE_KEY:
            return _("Remote Accounts");

        case IMP_Ftree::SHARED_KEY:
            return _("Shared");

        case IMP_Ftree::VFOLDER_KEY:
            return _("Virtual Folders");
        }

        /* Handle remote mailboxes. */
        if ($this->remote) {
            return $injector->getInstance('IMP_Remote')->label($this->_mbox);
        }

        $ns_info = $this->namespace_info;
        $out = $this->_mbox;

        if (!is_null($ns_info)) {
            /* Return translated namespace information. */
            if (!empty($ns_info['translation']) && $this->namespace) {
                $this->_cache[self::CACHE_DISPLAY] = $ns_info['translation'];
                $this->_changed = self::CHANGED_YES;
                return $ns_info['translation'];
            }

            /* Strip namespace information. */
            if (!empty($ns_info['name']) &&
                ($ns_info['type'] == Horde_Imap_Client::NS_PERSONAL) &&
                (substr($this->_mbox, 0, strlen($ns_info['name'])) == $ns_info['name'])) {
                $out = substr($this->_mbox, strlen($ns_info['name']));
            }
        }

        if ($notranslate) {
            return $out;
        }

        /* Substitute any translated prefix text. */
        $sub = array(
            'INBOX' => _("Inbox")
        );

        /* Bug #9971: Special mailboxes can be empty IMP_Mailbox objects -
         * catch this with the strlen check below. */
        foreach ($this->getSpecialMailboxes() as $key => $val) {
            switch ($key) {
            case self::SPECIAL_COMPOSETEMPLATES:
                $sub[strval($val)] = _("Templates");
                break;

            case self::SPECIAL_DRAFTS:
                $sub[strval($val)] = _("Drafts");
                break;

            case self::SPECIAL_SENT:
                if (count($val) == 1) {
                    $sub[strval(reset($val))] = _("Sent");
                } else {
                    $sent = self::getPref(self::MBOX_SENT);
                    foreach ($val as $mbox) {
                        if ($mbox == $sent) {
                            $sub[strval($mbox)] = _("Sent");
                            break;
                        }
                    }
                }
                break;

            case self::SPECIAL_SPAM:
                $sub[strval($val)] = _("Spam");
                break;

            case self::SPECIAL_TRASH:
                $sub[strval($val)] = _("Trash");
                break;
            }
        }

        foreach ($sub as $key => $val) {
            if (strlen($key) &&
                (($key != 'INBOX') || ($this->_mbox == $out)) &&
                strpos($this->_mbox, $key) === 0) {
                $len = strlen($key);
                if ((strlen($this->_mbox) == $len) || ($this->_mbox[$len] == (is_null($ns_info) ? '' : $ns_info['delimiter']))) {
                    $out = substr_replace($this->_mbox, $val, 0, $len);
                    break;
                }
            }
        }

        $this->_cache[self::CACHE_DISPLAY] = $out;
        $this->_changed = self::CHANGED_YES;

        return $out;
    }

    /**
     * Return icon information.
     *
     * @return object  Object with the following properties:
     *   - alt
     *   - class
     *   - icon
     *   - iconopen
     *   - user_icon
     */
    protected function _getIcon()
    {
        $info = new stdClass;
        $info->iconopen = null;
        $info->user_icon = false;

        if ($this->container) {
            /* We are dealing with folders here. */
            if ($this->is_open) {
                $info->alt = _("Opened Folder");
                $info->class = 'folderopenImg';
                $info->icon = 'folders/open.png';
            } else {
                $info->alt = _("Folder");
                $info->class = 'folderImg';
                $info->icon = 'folders/folder.png';
                $info->iconopen = Horde_Themes::img('folders/open.png');
            }
        } else {
            $special = $this->getSpecialMailboxes();

            switch ($this->_mbox) {
            case 'INBOX':
                $info->alt = _("Inbox");
                $info->class = 'inboxImg';
                $info->icon = 'folders/inbox.png';
                break;

            case $special[self::SPECIAL_COMPOSETEMPLATES]:
                $info->alt = ("Templates");
                $info->class = 'composetemplatesImg';
                $info->icon = 'folders/drafts.png';
                break;

            case $special[self::SPECIAL_DRAFTS]:
                $info->alt = _("Drafts");
                $info->class = 'draftsImg';
                $info->icon = 'folders/drafts.png';
                break;

            case $special[self::SPECIAL_SPAM]:
                $info->alt = _("Spam");
                $info->class = 'spamImg';
                $info->icon = 'folders/spam.png';
                break;

            case $special[self::SPECIAL_TRASH]:
                $info->alt = _("Trash");
                $info->class = 'trashImg';
                $info->icon = 'folders/trash.png';
                break;

            default:
                if (in_array($this->_mbox, $special[self::SPECIAL_SENT])) {
                    $info->alt = _("Sent");
                    $info->class = 'sentImg';
                    $info->icon = 'folders/sent.png';
                } else {
                    $info->alt = in_array($this->_mbox, $special[self::SPECIAL_USER])
                        ? $this->display
                        : _("Mailbox");
                    if ($this->is_open) {
                        $info->class = 'folderopenImg';
                        $info->icon = 'folders/open.png';
                    } else {
                        $info->class = 'folderImg';
                        $info->icon = 'folders/folder.png';
                    }
                }
                break;
            }

            /* Virtual folders. */
            if ($this->vfolder) {
                $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
                if ($imp_search->isVTrash($this->_mbox)) {
                    $info->alt = $imp_search[$this->_mbox]->label;
                    $info->class = 'trashImg';
                    $info->icon = 'folders/trash.png';
                } elseif ($imp_search->isVinbox($this->_mbox)) {
                    $info->alt = $imp_search[$this->_mbox]->label;
                    $info->class = 'inboxImg';
                    $info->icon = 'folders/inbox.png';
                }
            }
        }

        /* Overwrite the icon information now. */
        if (empty($this->_cache[self::CACHE_ICONS]) &&
            self::$_temp[self::CACHE_HASICONHOOK]) {
            if (!isset(self::$_temp[self::CACHE_ICONHOOK])) {
                self::$_temp[self::CACHE_ICONHOOK] = Horde::callHook('mbox_icons', array(), 'imp');
            }

            if (isset(self::$_temp[self::CACHE_ICONHOOK][$this->_mbox])) {
                $this->_cache[self::CACHE_ICONS] = self::$_temp[self::CACHE_ICONHOOK][$this->_mbox];
                $this->_changed = self::CHANGED_YES;
            }
        }

        if (!empty($this->_cache[self::CACHE_ICONS])) {
            $mi = $this->_cache[self::CACHE_ICONS];

            if (isset($mi['alt'])) {
                $info->alt = $mi['alt'];
            }
            $info->icon = strval($mi['icon']);
            $info->user_icon = true;
        } elseif ($info->icon) {
            $info->icon = Horde_Themes::img($info->icon);
        }

        return $info;
    }

    /**
     * Do the necessary cleanup/cache updates when deleting mailboxes.
     *
     * @param array $deleted  The list of deleted mailboxes.
     */
    protected function _onDelete($deleted)
    {
        /* Clear the mailboxes from the sort prefs. */
        foreach ($this->get($deleted) as $val) {
            $val->setSort(null, null, true);
        }
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return json_encode(array(
            $this->_mbox,
            $this->_cache
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        list(
            $this->_mbox,
            $this->_cache
        ) = json_decode($data, true);
    }

}
