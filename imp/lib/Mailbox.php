<?php
/**
 * The IMP_Mailbox class acts as a clearinghouse for actions related to a
 * mailbox.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 *
 * @property string $abbrev_label  Abbreviated version of $label - displays
 *                                 only the bare mailbox name (no parents).
 * @property boolean $access_creatembox  Can sub mailboxes be created?
 * @property boolean $access_deletembox  Can this mailbox be deleted?
 * @property boolean $access_deletemsgs  Can messages be deleted in this
 *                                       mailbox?
 * @property boolean $access_expunge  Can messages be expunged in this
 *                                    mailbox?
 * @property boolean $access_filters  Is filtering available?
 * @property boolean $access_sort  Is sorting available?
 * @property boolean $access_sortthread  Is thread sort available?
 * @property mixed $acl  Either an ACL object for the mailbox, or null if
 *                       no ACL found for the mailbox.
 * @property string $basename  The basename of the mailbox (UTF-8).
 * @property string $cacheid  Cache ID for the mailbox.
 * @property string $cacheid_date  Cache ID for the mailbox, with added date
 *                                 information.
 * @property boolean $children  Does the element have children?
 * @property boolean $container  Is this a container element?
 * @property string $display  Display version of mailbox. Special mailboxes
 *                            are replaced with localized strings and
 *                            namespace information is removed.
 * @property string $display_html  $display that has been HTML encoded.
 * @property boolean $drafts  Is this a Drafts mailbox?
 * @property boolean $editquery  Can this search query be edited?
 * @property boolean $editvfolder  Can this virtual folder be edited?
 * @property boolean $exists  Does this mailbox exist on the IMAP server?
 * @property boolean $fixed  Is this mailbox fixed (i.e. unchangable)?
 * @property string $form_to  Converts this mailbox to a form representation.
 * @property boolean $is_open  Is this level expanded?
 * @property boolean $is_trash  Is this a trash folder?
 * @property object $icon  Icon information for the mailbox. Properties:
 *   - alt: (string) The alt text for the icon.
 *   - class: (string) The CSS class name.
 *   - icon: (Horde_Themes_Image) The icon graphic to use.
 *   - iconopen: (Horde_Themes_Image) The openicon to use.
 *   - user_icon: (boolean) Use a user defined icon?
 * @property boolean $inbox)  Is this the INBOX?
 * @property boolean $invisible  Is this mailbox invisible?
 * @property string $label  The mailbox label. Essentially is $display that
 *                          can be modified by user hook.
 * @property integer $level  The child level of this element.
 * @property string $namespace  Is this a namespace element?
 * @property IMP_Mailbox $namespace_append  The mailbox with necessary
 *                                          namespace information appended.
 * @property string $namespace_delimiter  The delimiter for this namespace.
 * @property array $namespace_info  See IMP_Imap::getNamespace().
 * @property boolean $nonimap  Is this a non-IMAP element?
 * @property IMP_Mailbox $parent  The parent element. Returns null if no
 *                                parent.
 * @property IMP_Imap_PermanentFlags $permflags  Return the list of permanent
 *                                               flags available to set in the
 *                                               mailbox.
 * @property boolean $polled  Show polled information?
 * @property object $poll_info  Poll information for the mailbox. Properties:
 *   - msgs: (integer) The number of total messages in the element, if polled.
 *   - recent: (integer) The number of new messages in the element, if polled.
 *   - unseen: (integer) The number of unseen messages in the element, if
 *             polled.
 * @property string $pref_from  Convert mailbox name from preference storage.
 * @property string $pref_to  Convert mailbox name to preference storage.
 * @property boolean $query  Is this a search query?
 * @property boolean $readonly  Is this mailbox read-only?
 * @property boolean $search  Is this a search mailbox?
 * @property boolean $special  Is this is a "special" element?
 * @property boolean $special_outgoing  Is this a "special" element dealing
 *                                      with outgoing messages?
 * @property boolean $specialvfolder  Is this a "special" virtual folder?
 * @property boolean $sub  Is this mailbox subscribed to?
 * @property array $subfolders  Returns the list of subfolders as mailbox
 *                              objects (including the current mailbox).
 * @property array $subfolders_only  Returns the list of subfolders as mailbox
 *                                   objects (NOT including the current
 *                                   mailbox).
 * @property string $uidvalid  Returns the UIDVALIDITY string. Throws an
 *                             IMP_Exception on error.
 * @property string $value  The value of this element (i.e. IMAP mailbox
 *                          name). In UTF7-IMAP.
 * @property boolean $vfolder  Is this a virtual folder?
 * @property boolean $vfolder_container  Is this the virtual folder container?
 * @property boolean $vinbox  Is this the virtual inbox?
 * @property boolean $vtrash  Is this the virtual trash?
 */
class IMP_Mailbox implements Serializable
{
    /* Changed constants. */
    const CHANGED_NO = 0;
    const CHANGED_YES = 1;
    const CHANGED_DELETE = 2;

    /* Special mailbox identifiers. */
    const SPECIAL_DRAFTS = 'drafts';
    const SPECIAL_SENT = 'sent';
    const SPECIAL_SPAM = 'spam';
    const SPECIAL_TRASH = 'trash';

    /* Cache identifiers. */
    // (array) ACL rights
    const CACHE_ACL = 'a';
    // (string) Display string
    const CACHE_DISPLAY = 'd';
    // (array) Icons array
    const CACHE_ICONS = 'i';
    // (array) Namespace information
    const CACHE_NAMESPACE = 'n';
    // (boolean) Read-only?
    const CACHE_READONLY = 'ro';
    // (integer) UIDVALIDITY
    const CACHE_UIDVALIDITY = 'v';

    /* Cache identifiers - temporary data. */
    const CACHE_HASICONHOOK = 'ih';
    const CACHE_ICONHOOK = 'ic';
    const CACHE_HASLABELHOOK = 'lh';
    const CACHE_HIDEDELETED = 'hd';
    const CACHE_READONLYHOOK = 'roh';
    const CACHE_SPECIALMBOXES = 's';

    /**
     * Cached data.
     *
     * @var array
     */
    public $cache = array();

    /**
     * Has this object changed?
     *
     * @var integer
     */
    public $changed = self::CHANGED_NO;

    /**
     * Temporary data from importMbox().
     *
     * @var array
     */
    protected $_import;

    /**
     * Temporary cached data.  Used among all instances.
     *
     * @var array
     */
    static protected $_temp = array();

    /**
     * The full IMAP mailbox name.
     *
     * @var string
     */
    protected $_mbox;

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

        try {
            return $GLOBALS['injector']
                ->getInstance('IMP_Factory_Mailbox')
                ->create(strval($mbox));
        } catch (IMP_Exception $e) {
            return null;
        }
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
        if (strlen($mbox) == 0) {
            throw new IMP_Exception('Mailbox name must not be empty.');
        }

        $this->_mbox = $mbox;

        if (!isset(self::$_temp[self::CACHE_HASICONHOOK])) {
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
            return (($pos = strrpos($label, $this->namespace_delimiter)) === false)
                ? $label
                : substr($label, $pos + 1);

        case 'access_creatembox':
            return (!($acl = $this->acl) ||
                    ($acl[Horde_Imap_Client::ACL_CREATEMBOX]));

        case 'access_deletembox':
            return (!($acl = $this->acl) ||
                    ($acl[Horde_Imap_Client::ACL_DELETEMBOX]));

        case 'access_deletemsgs':
            return (!$this->readonly &&
                    (!($acl = $this->acl) ||
                    ($acl[Horde_Imap_Client::ACL_DELETEMSGS])));

        case 'access_expunge':
            return (!$this->readonly &&
                    (!($acl = $this->acl) ||
                    ($acl[Horde_Imap_Client::ACL_EXPUNGE])));

        case 'access_filters':
            return !$this->search &&
                   !$injector->getInstance('IMP_Factory_Imap')->create()->pop3;

        case 'access_sort':
            /* Although possible to abstract other sorting methods, all other
             * non-sequence methods require a download of ALL messages, which
             * is too much overhead.*/
            return !$injector->getInstance('IMP_Factory_Imap')->create()->pop3;

        case 'access_sortthread':
            /* Thread sort is always available for IMAP servers, since
             * Horde_Imap_Client_Socket has a built-in ORDEREDSUBJECT
             * implementation. We will always prefer REFERENCES, but will
             * fallback to ORDEREDSUBJECT if the server doesn't support THREAD
             * sorting. */
            return ($injector->getInstance('IMP_Factory_Imap')->create()->imap &&
                    !$this->search);

        case 'acl':
            if (isset($this->cache[self::CACHE_ACL])) {
                return is_null($this->cache[self::CACHE_ACL])
                    ? null
                    : new Horde_Imap_Client_Data_Acl($this->cache[self::CACHE_ACL]);
            }

            $acl = $this->cache[self::CACHE_ACL] = null;
            $this->changed = self::CHANGED_YES;

            if (!$this->nonimap) {
                try {
                    $acl = $injector->getInstance('IMP_Imap_Acl')->getACL($this, true);
                    /* Store string representation of ACL for a more compact
                     * serialized format. */
                    $this->cache[self::CACHE_ACL] = strval($acl);
                } catch (IMP_Exception $e) {}
            }

            return $acl;

        case 'basename':
            if ($this->nonimap) {
                return $this->label;
            }

            $basename = (($pos = strrpos($this->_mbox, $this->namespace_delimiter)) === false)
                ? $this->_mbox
                : substr($this->_mbox, $pos + 1);

            return Horde_String::convertCharset($basename, 'UTF7-IMAP', 'UTF-8');

        case 'cacheid':
        case 'cacheid_date':
            return $this->_getCacheID($key == 'cacheid_date');

        case 'children':
            return $injector->getInstance('IMP_Imap_Tree')->hasChildren($this->_mbox);

        case 'container':
            return $injector->getInstance('IMP_Imap_Tree')->isContainer($this->_mbox);

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
            if ($ob = $this->getSearchOb()) {
                return $ob->enabled;
            }

            $imaptree = $injector->getInstance('IMP_Imap_Tree');
            if (isset($imaptree[$this->_mbox])) {
                return !$imaptree[$this->_mbox]->container;
            }

            try {
                return (bool)$injector->getInstance('IMP_Factory_Imap')->create()->listMailboxes($this->_mbox, array('flat' => true));
            } catch (IMP_Imap_Exception $e) {
                return false;
            }

        case 'fixed':
            return (!empty($GLOBALS['conf']['server']['fixed_folders']) &&
                    in_array($this->pref_to, $GLOBALS['conf']['server']['fixed_folders']));

        case 'form_to':
            return $this->formTo($this->_mbox);

        case 'is_open':
            return $injector->getInstance('IMP_Imap_Tree')->isOpen($this->_mbox);

        case 'is_trash':
            return (self::getPref('trash_folder') == $this) || $this->vtrash;

        case 'icon':
            return $this->_getIcon();

        case 'inbox':
            return (strcasecmp($this->_mbox, 'INBOX') === 0);

        case 'invisible':
            return $injector->getInstance('IMP_Imap_Tree')->isInvisible($this->_mbox);

        case 'label':
            /* Returns the plain text label that is displayed for the current
             * mailbox, replacing virtual search mailboxes with an appropriate
             * description, removing namespace and mailbox prefix information
             * from what is shown to the user, and passing the label through a
             * user-defined hook. */
            $imp_search = $injector->getInstance('IMP_Search');
            $label = ($ob = $imp_search[$this->_mbox])
                ? $ob->label
                : $this->_getDisplay();

            return self::$_temp[self::CACHE_HASLABELHOOK]
                ? Horde::callHook('mbox_label', array($this->_mbox, $label), 'imp')
                : $label;

        case 'level':
            $elt = $injector->getInstance('IMP_Imap_Tree')->getElement($this->_mbox);
            return $elt
                ? $elt['c']
                : 0;

        case 'namespace':
            return $injector->getInstance('IMP_Imap_Tree')->isNamespace($this->_mbox);

        case 'namespace_append':
            $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
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

            if (isset($this->cache[self::CACHE_NAMESPACE])) {
                $ns = $this->cache[self::CACHE_NAMESPACE];

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

            $ns_info = $injector->getInstance('IMP_Factory_Imap')->create()->getNamespace($this->_mbox);
            if (is_null($ns_info)) {
                $this->cache[self::CACHE_NAMESPACE] = null;
            } else {
                /* Store data compressed in the cache array. */
                $this->cache[self::CACHE_NAMESPACE] = array();
                foreach ($keys as $id => $key) {
                    if ($ns_info[$key]) {
                        $this->cache[self::CACHE_NAMESPACE][$id] = $ns_info[$key];
                    }
                }
            }

            $this->changed = self::CHANGED_YES;

            return $ns_info;

        case 'nonimap':
            return ($this->search ||
                    $injector->getInstance('IMP_Imap_Tree')->isNonImapElt($this->_mbox));

        case 'parent':
            $elt = $injector->getInstance('IMP_Imap_Tree')->getElement($this->_mbox);
            return $elt
                ? self::get($elt['p'])
                : null;

        case 'permflags':
            $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

            if ($imp_imap->access(IMP_Imap::ACCESS_FLAGS)) {
                try {
                    /* Make sure we are in R/W mailbox mode (SELECT). No flags
                     * are allowed in EXAMINE mode. */
                    $imp_imap->openMailbox($this->_mbox, Horde_Imap_Client::OPEN_READWRITE);
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
                if ($msgs_info = $injector->getInstance('IMP_Factory_Imap')->create()->status($this->_mbox, Horde_Imap_Client::STATUS_RECENT | Horde_Imap_Client::STATUS_UNSEEN | Horde_Imap_Client::STATUS_MESSAGES)) {
                    if (!empty($msgs_info['recent'])) {
                        $info->recent = intval($msgs_info['recent']);
                    }
                    $info->msgs = intval($msgs_info['messages']);
                    $info->unseen = intval($msgs_info['unseen']);
                }
            } catch (IMP_Imap_Exception $e) {}

            return $info;

        case 'polled':
            return !$this->search &&
                   $injector->getInstance('IMP_Imap_Tree')->isPolled($this->_mbox);

        case 'pref_from':
            return $this->prefFrom($this->_mbox);

        case 'pref_to':
            return $this->prefTo($this->_mbox);

        case 'query':
            return $injector->getInstance('IMP_Search')->isQuery($this->_mbox);

        case 'readonly':
            if (isset($this->cache[self::CACHE_READONLY])) {
                return $this->cache[self::CACHE_READONLY];
            }

            $this->cache[self::CACHE_READONLY] = false;
            $this->changed = self::CHANGED_YES;

            /* This check works for regular and search mailboxes. */
            if (empty(self::$_temp[self::CACHE_READONLYHOOK])) {
                try {
                    if (Horde::callHook('mbox_readonly', array($this->_mbox), 'imp')) {
                        $this->cache[self::CACHE_READONLY] = true;
                    }
                } catch (Horde_Exception_HookNotSet $e) {
                    self::$_temp[self::CACHE_READONLYHOOK] = true;
                }
            }

            /* The UIDNOTSTICKY check would go here. */

            return $this->cache[self::CACHE_READONLY];

        case 'search':
            return $injector->getInstance('IMP_Search')->isSearchMbox($this->_mbox);

        case 'special':
            $special = $this->getSpecialMailboxes();

            switch ($this->_mbox) {
            case 'INBOX':
            case $special[self::SPECIAL_DRAFTS]:
            case $special[self::SPECIAL_SPAM]:
            case $special[self::SPECIAL_TRASH]:
                return true;
            }

            return in_array($this->_mbox, $special[self::SPECIAL_SENT]);

        case 'special_outgoing':
            $special = $this->getSpecialMailboxes();

            return in_array($this->_mbox, array_merge(
                array(
                    $special[self::SPECIAL_DRAFTS]
                ),
                $special[self::SPECIAL_SENT]
            ));

        case 'specialvfolder':
            return !$this->editvfolder;

        case 'sub':
            return $injector->getInstance('IMP_Imap_Tree')->isSubscribed($this->_mbox);

        case 'subfolders':
            return $this->get(array_merge(array($this->_mbox), $this->subfolders_only));

        case 'subfolders_only':
            return $this->get($injector->getInstance('IMP_Factory_Imap')->create()->listMailboxes($this->_mbox . $this->namespace_delimiter . '*', null, array('flat' => true)));

        case 'uidvalid':
            $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

            // POP3 does not support UIDVALIDITY.
            if ($imp_imap->pop3) {
                return;
            }

            $status = $imp_imap->status($this->_mbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
            if (($first = !isset($this->cache[self::CACHE_UIDVALIDITY])) ||
                ($status['uidvalidity'] != $this->cache[self::CACHE_UIDVALIDITY])) {
                $this->cache[self::CACHE_UIDVALIDITY] = $status['uidvalidity'];
                $this->changed = self::CHANGED_YES;

                if (!$first) {
                    throw new IMP_Exception(_("Mailbox structure on server has changed."));
                }
            }

            return $this->cache[self::CACHE_UIDVALIDITY];

        case 'value':
            return $this->_mbox;

        case 'vfolder':
            return $injector->getInstance('IMP_Search')->isVFolder($this->_mbox);

        case 'vfolder_container':
            return ($this->_mbox == IMP_Imap_Tree::VFOLDER_KEY);

        case 'vinbox':
            return $injector->getInstance('IMP_Search')->isVinbox($this->_mbox);

        case 'vtrash':
            return $injector->getInstance('IMP_Search')->isVtrash($this->_mbox);
        }

        return false;
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
        global $injector, $notification, $prefs, $registry;

        if ($this->exists) {
            return true;
        }

        /* Check permissions. */
        $perms = $injector->getInstance('Horde_Core_Perms');
        if (!$perms->hasAppPermission('create_folders')) {
            Horde::permissionDeniedError(
                'imp',
                'create_folders',
                _("You are not allowed to create folders.")
            );
            return false;
        } elseif (!$perms->hasAppPermission('max_folders')) {
            Horde::permissionDeniedError(
                'imp',
                'max_folders',
                sprintf(_("You are not allowed to create more than %d folders."), $injector->getInstance('Horde_Perms')->getPermissions('max_folders', $registry->getAuth()))
            );
            return false;
        }

        /* Special use flags. */
        $special_use = isset($opts['special_use'])
            ? $opts['special_use']
            : array();

        /* Attempt to create the mailbox. */
        try {
            $injector->getInstance('IMP_Factory_Imap')->create()->createMailbox($this->_mbox, array('special_use' => $special_use));
        } catch (IMP_Imap_Exception $e) {
            if ($e->getCode() == Horde_Imap_Client_Exception::USEATTR) {
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
        $injector->getInstance('IMP_Imap_Tree')->insert($this->_mbox);

        return true;
    }

    /**
     * Deletes mailbox.
     *
     * @param boolean $force  Delete even if fixed?
     *
     * @return boolean  True on success.
     */
    public function delete($force = false)
    {
        global $conf, $injector, $notification;

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

        if ((!$force && $this->fixed) || !$this->access_deletembox)  {
            $notification->push(sprintf(_("The mailbox \"%s\" may not be deleted."), $this->display), 'horde.error');
            return false;
        }

        try {
            $injector->getInstance('IMP_Factory_Imap')->create()->deleteMailbox($this->_mbox);
            $notification->push(sprintf(_("The mailbox \"%s\" was successfully deleted."), $this->display), 'horde.success');
        } catch (IMP_Imap_Exception $e) {
            $e->notify(sprintf(_("The mailbox \"%s\" was not deleted. This is what the server said"), $this->display) . ': ' . $e->getMessage());
            return false;
        }

        $injector->getInstance('IMP_Imap_Tree')->delete($this->_mbox);
        $this->_onDelete(array($this->_mbox));

        return true;
    }

    /**
     * Rename this mailbox on the server. The subscription status remains the
     * same.  All subfolders will also be renamed.
     *
     * @param string $new     The new mailbox name (UTF7-IMAP).
     * @param boolean $force  Rename mailbox even if it is fixed?
     *
     * @return boolean  True on success
     */
    public function rename($new_name, $force = false)
    {
        global $injector, $notification;

        /* Don't try to rename to an empty string. */
        if (!strlen($new_name)) {
            return false;
        }

        if ((!$force && $this->fixed) || !$this->access_deletembox) {
            $notification->push(sprintf(_("The mailbox \"%s\" may not be renamed."), $this->display), 'horde.error');
            return false;
        }

        $new_mbox = $this->get($new_name);
        $old_list = $this->subfolders;

        try {
            $injector->getInstance('IMP_Factory_Imap')->create()->renameMailbox($this->_mbox, strval($new_mbox));
        } catch (IMP_Imap_Exception $e) {
            $e->notify(sprintf(_("Renaming \"%s\" to \"%s\" failed. This is what the server said"), $this->display, $new_mbox->display) . ': ' . $e->getMessage());
            return false;
        }

        $notification->push(sprintf(_("The mailbox \"%s\" was successfully renamed to \"%s\"."), $this->display, $new_mbox->display), 'horde.success');

        $injector->getInstance('IMP_Imap_Tree')->rename($this->_mbox, strval($new_mbox));
        $this->_onDelete($old_list);

        return true;
    }

    /**
     * Subscribe/unsubscribe to an IMAP mailbox.
     *
     * @param boolean $sub  True to subscribe, false to unsubscribe.

     * @return boolean  True on success.
     */
    public function subscribe($sub)
    {
        global $injector, $notification;

        if (!$sub && $this->inbox) {
            $notification->push(sprintf(_("You cannot unsubscribe from \"%s\"."), $this->display), 'horde.error');
            return false;
        }

        try {
            $injector->getInstance('IMP_Factory_Imap')->create()->subscribeMailbox($this->_mbox, $sub);
        } catch (IMP_Imap_Exception $e) {
            if ($sub) {
                $e->notify(sprintf(_("You were not subscribed to \"%s\". Here is what the server said"), $this->display) . ': ' . $e->getMessage());
            } else {
                $e->notify(sprintf(_("You were not unsubscribed from \"%s\". Here is what the server said"), $this->display) . ': ' . $e->getMessage());
            }
            return false;
        }

        if ($sub) {
            $notification->push(sprintf(_("You were successfully subscribed to \"%s\"."), $this->display), 'horde.success');
            $injector->getInstance('IMP_Imap_Tree')->subscribe($this->_mbox);
        } else {
            $notification->push(sprintf(_("You were successfully unsubscribed from \"%s\"."), $this->display), 'horde.success');
            $injector->getInstance('IMP_Imap_Tree')->unsubscribe($this->_mbox);
        }

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
     * Return the mailbox list object.
     *
     * @param IMP_Indices $indices  See IMP_Factory_MailboxList::__construct().
     *
     * @return IMP_Mailbox_List  See IMP_Factory_MailboxList::__construct().
     */
    public function getListOb($indices = null)
    {
        return $GLOBALS['injector']->getInstance('IMP_Factory_MailboxList')->create($this, $indices);
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
     * @return array  An array with the following keys:
     *   - by: (integer) Sort type.
     *   - dir:(integer) Sort direction.
     *   - locked: (boolean) Is sort locked for the mailbox?
     */
    public function getSort($convert = false)
    {
        global $prefs;

        $prefmbox = $this->search
            ? $this->_mbox
            : $this->pref_from;

        $sortpref = @unserialize($prefs->getValue('sortpref'));
        $entry = isset($sortpref[$prefmbox])
            ? $sortpref[$prefmbox]
            : array();

        if (!isset($entry['b'])) {
            $sortby = $prefs->getValue('sortby');
        }

        $ob = array(
            'by' => isset($entry['b']) ? $entry['b'] : $sortby,
            'dir' => isset($entry['d']) ? $entry['d'] : $prefs->getValue('sortdir'),
            'locked' => $prefs->isLocked('sortpref')
        );

        /* Restrict to sequence sorting only. */
        if (!$this->access_sort) {
            $ob['by'] = Horde_Imap_Client::SORT_SEQUENCE;
            return $ob;
        }

        switch ($ob['by']) {
        case Horde_Imap_Client::SORT_THREAD:
            /* Can't do threaded searches in search mailboxes. */
            if (!$this->access_sortthread) {
                $ob['by'] = IMP::IMAP_SORT_DATE;
            }
            break;

        case Horde_Imap_Client::SORT_FROM:
            /* If the preference is to sort by From Address, when we are
             * in the Drafts or Sent folders, sort by To Address. */
            if ($this->special_outgoing) {
                $ob['by'] = Horde_Imap_Client::SORT_TO;
            }
            break;

        case Horde_Imap_Client::SORT_TO:
            if (!$this->special_outgoing) {
                $ob['by'] = Horde_Imap_Client::SORT_FROM;
            }
            break;
        }

        if ($convert && ($ob['by'] == IMP::IMAP_SORT_DATE)) {
            $ob['by'] = $prefs->getValue('sortdate');
        }

        /* Sanity check: make sure we have some sort of sort value. */
        if (!$ob['by']) {
            $ob['by'] = Horde_Imap_Client::SORT_ARRIVAL;
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
        global $injector, $prefs;

        $entry = array();
        $sortpref = @unserialize($prefs->getValue('sortpref'));

        $prefmbox = $this->search
            ? $this->_mbox
            : $this->pref_from;

        if ($delete) {
            unset($sortpref[$prefmbox]);
        } else {
            if (!is_null($by)) {
                $entry['b'] = $by;
            }
            if (!is_null($dir)) {
                $entry['d'] = $dir;
            }

            if (!empty($entry)) {
                // TODO: convert using pref_to?
                $sortpref[$prefmbox] = isset($sortpref[$prefmbox])
                    ? array_merge($sortpref[$prefmbox], $entry)
                    : $entry;
            }
        }

        if ($delete || !empty($entry)) {
            $prefs->setValue('sortpref', serialize($sortpref));
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
        global $injector, $prefs;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
        if (!$imp_imap->access(IMP_Imap::ACCESS_FLAGS)) {
            return $imp_imap->imap;
        }

        $delhide = isset(self::$_temp[self::CACHE_HIDEDELETED])
            ? self::$_temp[self::CACHE_HIDEDELETED]
            : null;

        if (is_null($delhide)) {
            if ($prefs->getValue('use_trash')) {
                /* If using Virtual Trash, only show deleted messages in
                 * the Virtual Trash mailbox. */
                $delhide = $this->get($prefs->getValue('trash_folder'))->vtrash
                    ? $this->vtrash
                    : $deleted;
            } elseif (!$prefs->getValue('delhide')) {
                $delhide = false;
            } else {
                /* Even if delhide is true, need to not hide if we are doing
                 * thread sort. */
                $sortpref = $this->getSort();
                $delhide = ($sortpref['by'] != Horde_Imap_Client::SORT_THREAD);
            }
        }

        if (!$deleted) {
            self::$_temp[self::CACHE_HIDEDELETED] = $delhide;
        }

        return $delhide;
    }

    /**
     * Sets the 'delhide' preference and clears necessary cached data.
     *
     * @param boolean $value  The value to set 'delhide' to.
     */
    public function setHideDeletedMsgs($value)
    {
        $GLOBALS['prefs']->setValue('delhide', $value);
        $this->expire(IMP_Mailbox::CACHE_HIDEDELETED);
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
            $results = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->search($this, $query, array(
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
            $this->cache = array();
        } else {
            $changed = false;

            if (!is_array($entries)) {
                $entries = array($entries);
            }

            foreach ($entries as $val) {
                $changed = isset($this->cache[$val]);
                unset($this->cache[$val], self::$_temp[$val]);
            }
        }

        if ($changed) {
            $this->changed = empty($this->cache)
                ? self::CHANGED_DELETE
                : self::CHANGED_YES;
        }
    }

    /**
     * Generate a URL using the current mailbox.
     *
     * @param string|Horde_Url $page  Page name to link to.
     * @param string $uid             The UID to use on the linked page.
     * @param string $tmailbox        The mailbox associated with $uid.
     * @param boolean $encode         Encode the argument separator?
     *
     * @return Horde_Url  URL to $page with any necessary mailbox information
     *                    added to the parameter list of the URL.
     */
    public function url($page, $uid = null, $tmailbox = null, $encode = true)
    {
        if ($page instanceof Horde_Url) {
            $url = clone $page;
        } else {
            if (($page != 'search.php') && (IMP::getViewMode() == 'dimp')) {
                $anchor = is_null($uid)
                    ? ('mbox:' . $this->form_to)
                    : ('msg:' . $this->getIndicesOb($uid)->formTo());
                return Horde::url('index.php')->setAnchor($anchor);
            }

            $url = Horde::url($page);
        }

        return $url->add($this->urlParams($uid, $tmailbox))->setRaw(!$encode);
    }

    /**
     * Returns list of URL parameters necessary to indicate current mailbox
     * status.
     *
     * @param string $uid       The UID to use on the linked page.
     * @param string $tmailbox  The mailbox associated with $uid to use on
     *                          the linked page.
     *
     * @return array  The list of parameters needed to indicate the current
     *                mailbox status.
     */
    public function urlParams($uid = null, $tmailbox = null)
    {
        $params = array('mailbox' => $this->form_to);
        if (!is_null($uid)) {
            $params['uid'] = $uid;
            if (!is_null($tmailbox) && ($this->_mbox != $tmailbox)) {
                $params['thismailbox'] = IMP_Mailbox::get($tmailbox)->form_to;
            }
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
     * Imports messages from a mbox (see RFC 4155) -or- a message source
     * (eml) file.
     *
     * @param string $fname  Filename containing the message data.
     * @param string $type   The MIME type of the message data.
     *
     * @return mixed  False (boolean) on fail or the number of messages
     *                imported (integer) on success.
     * @throws IMP_Exception
     */
    public function importMbox($fname, $type)
    {
        $fd = $format = $msg = null;

        if (!file_exists($fname)) {
            return false;
        }

        switch ($type) {
        case 'application/gzip':
        case 'application/x-gzip':
        case 'application/x-gzip-compressed':
            // No need to default to Horde_Compress because it uses zlib
            // also.
            if (in_array('compress.zlib', stream_get_wrappers())) {
                $fname = 'compress.zlib://' . $fname;
            }
            break;

        case 'application/x-bzip2':
        case 'application/x-bzip':
            if (in_array('compress.bzip2', stream_get_wrappers())) {
                $fname = 'compress.bzip2://' . $fname;
            }
            break;

        case 'application/zip':
        case 'application/x-compressed':
        case 'application/x-zip-compressed':
            if (in_array('zip', stream_get_wrappers())) {
                $fname = 'zip://' . $fname;
            } else {
                try {
                    $zip = Horde_Compress::factory('Zip');
                    if ($zip->canDecompress) {
                        $file_data = file_get_contents($fname);

                        $zip_info = $zip->decompress($file_data, array(
                            'action' => Horde_Compress_Zip::ZIP_LIST
                        ));

                        if (!empty($zip_info)) {
                            $fd = fopen('php://temp', 'r+');

                            foreach (array_keys($zip_info) as $key) {
                                fwrite($fd, $zip->decompress($file_data, array(
                                    'action' => Horde_Compress_Zip::ZIP_DATA,
                                    'info' => $zip_info,
                                    'key' => $key
                                )));
                            }

                            rewind($fd);
                        }
                    }
                } catch (Horde_Compress_Exception $e) {
                    if ($fd) {
                        fclose($fd);
                        $fd = null;
                    }
                }

                $fname = null;
            }
            break;
        }

        if (!is_null($fname)) {
            $fd = fopen($fname, 'r');
        }

        if (!$fd) {
            throw new IMP_Exception(_("The uploaded file cannot be opened"));
        }

        $this->_import = array(
            'data' => array(),
            'msgs' => 0,
            'size' => 0
        );

        while (!feof($fd)) {
            $line = fgets($fd);

            /* RFC 4155 - mbox format. */
            // TODO: Better preg for matching From line
            // See(?) http://code.iamcal.com/php/rfc822/
            if ((!$format || ($format == 'mbox')) &&
                preg_match('/^From (.+@.+|- )/', $line)) {
                $format = 'mbox';

                if ($msg) {
                    /* Send in chunks to take advantage of MULTIAPPEND (if
                     * available). */
                    $this->_importMbox($msg, true);
                }

                $msg = fopen('php://temp', 'r+');
            } elseif ($msg) {
                fwrite($msg, $line);
            } elseif (!$format && trim($line)) {
                /* Allow blank space at beginning of file. Anything else is
                 * treated as message input. */
                $format = 'eml';
                $msg = fopen('php://temp', 'r+');
                fwrite($msg, $line);
            }
        }
        fclose($fd);

        if ($msg) {
            $this->_importMbox($msg);
        }

        return $this->_import['msgs']
            ? $this->_import['msgs']
            : false;
    }

    /**
     * Helper for importMbox().
     *
     * @param resource $msg    Stream containing message data.
     * @param integer $buffer  Buffer messages before sending?
     */
    protected function _importMbox($msg, $buffer = false)
    {
        $this->_import['data'][] = array('data' => $msg);
        $this->_import['size'] += intval(ftell($msg));

        /* Buffer 5 MB of messages before sending. */
        if ($buffer && ($this->_import['size'] < 5242880)) {
            return;
        }

        try {
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->append($this->_mbox, $this->_import['data']);
            $this->_import['msgs'] += count($this->_import['data']);
        } catch (IMP_Imap_Exception $e) {}

        foreach ($this->_import['data'] as $val) {
            fclose($val['data']);
        }

        $this->_import['data'] = array();
        $this->_import['size'] = 0;
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
            : self::get(IMP::base64urlDecode($mbox));
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
            : IMP::base64urlEncode($mbox);
    }

    /**
     * Return the list of special mailboxes.
     *
     * @return array  A list of folders, with the self::SPECIAL_* constants as
     *                keys and values containing the IMP_Mailbox objects or
     *                null if the mailbox doesn't exist (self::SPECIAL_SENT
     *                contains an array of objects). These mailboxes are
     *                sorted in a logical order (see Ticket #10683).
     */
    static public function getSpecialMailboxes()
    {
        if (!isset(self::$_temp[self::CACHE_SPECIALMBOXES])) {
            self::$_temp[self::CACHE_SPECIALMBOXES] = array(
                self::SPECIAL_DRAFTS => self::getPref('drafts_folder'),
                self::SPECIAL_SENT => $GLOBALS['injector']->getInstance('IMP_Identity')->getAllSentmailFolders(),
                self::SPECIAL_SPAM => self::getPref('spam_folder'),
                self::SPECIAL_TRASH => $GLOBALS['prefs']->getValue('use_trash') ? self::getPref('trash_folder') : null
            );
        }

        return self::$_temp[self::CACHE_SPECIALMBOXES];
    }

    /**
     * Converts a mailbox name from a value stored in the preferences.
     *
     * @param string $mbox  The mailbox name as stored in a preference.
     *
     * @return string  The full IMAP mailbox name (UTF7-IMAP).
     */
    static public function prefFrom($mbox)
    {
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
        $def_ns = $imp_imap->defaultNamespace();
        $empty_ns = $imp_imap->getNamespace('');

        if (!is_null($empty_ns) &&
            strpos($mbox, $empty_ns['delimiter']) === 0) {
            /* Prefixed with delimiter => from empty namespace. */
            return substr($mbox, strlen($empty_ns['delimiter']));
        } elseif (($ns = $imp_imap->getNamespace($mbox, true)) == null) {
            /* No namespace prefix => from personal namespace. */
            return $def_ns['name'] . $mbox;
        }

        return $mbox;
    }

    /**
     * Converts a mailbox name to a value to be stored in a preference.
     *
     * @param string $mbox  The full IMAP mailbox name (UTF7-IMAP).
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
            ? 'D' . date('mdy')
            : '';

        if ($this->search) {
            return '1' . ($date ? '|' . $date : '');
        }

        $sortpref = $this->getSort(true);
        $addl = array($sortpref['by'], $sortpref['dir']);
        if ($date) {
            $addl[] = $date;
        }

        try {
            return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->getCacheId($this->_mbox, $addl);
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
        if (!$notranslate && isset($this->cache[self::CACHE_DISPLAY])) {
            return is_bool($this->cache[self::CACHE_DISPLAY])
                ? Horde_String::convertCharset($this->_mbox, 'UTF7-IMAP', 'UTF-8')
                : $this->cache[self::CACHE_DISPLAY];
        }

        /* Handle special container mailboxes. */
        switch ($this->_mbox) {
        case IMP_Imap_Tree::OTHER_KEY:
            return _("Other Users");

        case IMP_Imap_Tree::SHARED_KEY:
            return _("Shared");

        case IMP_Imap_Tree::VFOLDER_KEY:
            return _("Virtual Folders");
        }

        $ns_info = $this->namespace_info;
        $out = $this->_mbox;

        if (!is_null($ns_info)) {
            /* Return translated namespace information. */
            if (!empty($ns_info['translate']) && $this->namespace) {
                $d = Horde_String::convertCharset($ns_info['translate'], 'UTF7-IMAP', 'UTF-8');
                $this->_cache[self::CACHE_DISPLAY] = ($ns_info['translate'] == $this->_mbox)
                    ? true
                    : $d;
                $this->changed = self::CHANGED_YES;
                return $d;
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
            case self::SPECIAL_DRAFTS:
                $sub[strval($val)] = _("Drafts");
                break;

            case self::SPECIAL_SENT:
                if (count($val) == 1) {
                    $sub[strval(reset($val))] = _("Sent");
                } else {
                    $sent = self::getPref('sent_mail_folder');
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
                    $out = substr_replace($this->_mbox, Horde_String::convertCharset($val, 'UTF-8', 'UTF7-IMAP'), 0, $len);
                    break;
                }
            }
        }

        $d = Horde_String::convertCharset($out, 'UTF7-IMAP', 'UTF-8');
        $this->cache[self::CACHE_DISPLAY] = ($out == $this->_mbox)
            ? true
            : $d;
        $this->changed = self::CHANGED_YES;

        return $d;
    }

    /**
     * Return icon information.
     *
     * @return object  Object with the following properties:
     * <pre>
     * 'alt'
     * 'class'
     * 'icon'
     * 'iconopen'
     * 'user_icon'
     * </pre>
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
                    $info->alt = _("Mailbox");
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
        if (empty($this->cache[self::CACHE_ICONS]) &&
            self::$_temp[self::CACHE_HASICONHOOK]) {
            if (!isset(self::$_temp[self::CACHE_ICONHOOK])) {
                self::$_temp[self::CACHE_ICONHOOK] = Horde::callHook('mbox_icons', array(), 'imp');
            }

            if (isset(self::$_temp[self::CACHE_ICONHOOK][$this->_mbox])) {
                $this->cache[self::CACHE_ICONS] = self::$_temp[self::CACHE_ICONHOOK][$this->_mbox];
                $this->changed = self::CHANGED_YES;
            }
        }

        if (!empty($this->cache[self::CACHE_ICONS])) {
            $mi = $this->cache[self::CACHE_ICONS];

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
        /* Clear the mailbox from the sort prefs. */
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
            $this->cache
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        list(
            $this->_mbox,
            $this->cache
        ) = json_decode($data, true);
    }

}
