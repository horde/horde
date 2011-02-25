<?php
/**
 * The IMP_Mailbox class acts as a clearinghouse for actions related to a
 * mailbox.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 *
 * @property string $abbrev_label  Abbreviated version of $label - displays
 *                                 only the bare mailbox name (no parents).
 * @property string $cacheid  Cache ID for the mailbox.
 * @property boolean $children  Does the element have children?
 * @property boolean $container  Is this a container element?
 * @property string $display  Display version of mailbox. Special mailboxes
 *                            are replaced with localized strings and
 *                            namespace information is removed.
 * @property boolean $editvfolder  Can this virtual folder be edited?
 * @property boolean $exists  Does this mailbox exist on the IMAP server?
 * @property boolean $fixed  Is this mailbox fixed (i.e. unchangable)?
 * @property string $form_to  Converts this mailbox to a form representation.
 * @property boolean $is_open  Is this level expanded?
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
 * @property string $namespace_append  The mailbox with necessary namespace
 *                                     information appended.
 * @property array $namespace_info  TODO
 * @property boolean $nonimap  Is this a non-IMAP element?
 * @property array $parent  The parent element value.
 * @property boolean $polled  Show polled information?
 * @property object $poll_info  Poll information for the mailbox. Properties:
 *   - msgs: (integer) The number of total messages in the element, if polled.
 *   - recent: (integer) The number of new messages in the element, if polled.
 *   - unseen: (integer) The number of unseen messages in the element, if
 *             polled.
 * @property string $pref_from  Convert mailbox name from preference storage.
 * @property string $pref_to  Convert mailbox name to preference storage.
 * @property boolean $readonly  Is this mailbox read-only?
 * @property boolean $search  Is this a search mailbox?
 * @property boolean $special  Is this is a "special" element?
 * @property boolean $special_outgoing  Is this a "special" element dealing
 *                                      with outgoing messages?
 * @property boolean $specialvfolder  Is this a "special" virtual folder?
 * @property boolean $sub  Is this mailbox subscribed to?
 * @property array $subfolders  Returns the list of subfolders (including the
 *                              current mailbox).
 * @property boolean $threadsort Is thread sort available?
 * @property string $uidvalid  Returns the UIDVALIDITY string. Throws an
 *                             IMP_Exception on error.
 * @property string $value  The value of this element (i.e. IMAP mailbox
 *                          name). In UTF7-IMAP.
 * @property boolean $vfolder  Is this a virtual folder?
 * @property boolean $vinbox  Is this the virtual inbox?
 * @property boolean $vtrash  Is this the virtual trash?
 */
class IMP_Mailbox implements Serializable
{
    /**
     * Cache.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * The full IMAP mailbox name.
     *
     * @var string
     */
    protected $_mbox;

    /**
     * Cache special mailboxes.  Used among all instances.
     *
     * @var array
     */
    static protected $_specialCache;

    /**
     * Shortcut to obtaining mailbox object(s).
     *
     * @var mixed $mbox  The full IMAP mailbox name(s).
     *
     * @return mixed  The IMP_Mailbox object(s).
     */
    static public function get($mbox)
    {
        if (is_array($mbox)) {
            return array_filter(array_map(array(self, 'get'), $mbox));
        }

        try {
            return ($mbox instanceof IMP_Mailbox)
                ? $mbox
                : $GLOBALS['injector']->getInstance('IMP_Factory_Mailbox')->create($mbox);
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
    }

    /**
     */
    public function __toString()
    {
        return $this->_mbox;
    }

    /**
     */
    public function __get($key)
    {
        global $injector;

        switch ($key) {
        case 'abbrev_label':
            $elt = $injector->getInstance('IMP_Imap_Tree')->getElement($this->_mbox);
            return $elt
                ? $elt['l']
                : $this->label;

        case 'cacheid':
            return $this->_getCacheID();

        case 'children':
            return $injector->getInstance('IMP_Imap_Tree')->hasChildren($this->_mbox);

        case 'container':
            return $injector->getInstance('IMP_Imap_Tree')->isContainer($this->_mbox);

        case 'display':
            return $this->nonimap
                ? $this->label
                : $this->_getDisplay();

        case 'display_notranslate':
            return $this->nonimap
                ? $this->label
                : $this->_getDisplay(true);

        case 'editvfolder':
            return $injector->getInstance('IMP_Search')->isVFolder($this->_mbox, true);

        case 'exists':
            $imaptree = $injector->getInstance('IMP_Imap_Tree');
            if (isset($imaptree[$this->_mbox])) {
                return !$imaptree[$this->_mbox]->container;
            }

            try {
                $ret = $injector->getInstance('IMP_Factory_Imap')->create()->listMailboxes($this->_mbox, array('flat' => true));
                return !empty($ret);
            } catch (Horde_Imap_Client_Exception $e) {
                return false;
            }

        case 'fixed':
            return (!empty($GLOBALS['conf']['server']['fixed_folders']) &&
                    in_array($this->pref_to, $GLOBALS['conf']['server']['fixed_folders']));

        case 'form_to':
            return $this->formTo($this->_mbox);

        case 'is_open':
            return $injector->getInstance('IMP_Imap_Tree')->isOpen($this->_mbox);

        case 'icon':
            return $this->_getIcon();

        case 'inbox':
            return (strcasecmp($folder, 'INBOX') === 0);

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

            try {
                return Horde::callHook('mbox_label', array($this->_mbox, $label), 'imp');
            } catch (Horde_Exception_HookNotSet $e) {
                return $label;
            }

        case 'level':
            $elt = $injector->getInstance('IMP_Imap_Tree')->getElement($this->_mbox);
            return $elt
                ? $elt['c']
                : 0;

        case 'name':
            return htmlspecialchars($this->label);

        case 'namespace':
            return $injector->getInstance('IMP_Imap_Tree')->isNamespace($this->_mbox);

        case 'namespace_append':
            return self::get($injector->getInstance('IMP_Factory_Imap')->create()->appendNamespace($this->_mbox));

        case 'namespace_info':
            return $injector->getInstance('IMP_Factory_Imap')->create()->getNamespace($this->_mbox);

        case 'nonimap':
            return $injector->getInstance('IMP_Imap_Tree')->isNonImapElt($this->_mbox);

        case 'parent':
            $elt = $injector->getInstance('IMP_Imap_Tree')->getElement($this->_mbox);
            return $elt
                ? $elt['p']
                : '';

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
            } catch (Horde_Imap_Client_Exception $e) {}

            return $info;

        case 'polled':
            return $injector->getInstance('IMP_Imap_Tree')->isPolled($this->_mbox);

        case 'pref_from':
            return $this->prefFrom($this->_mbox);

        case 'pref_to':
            return $this->prefTo($this->_mbox);

        case 'readonly':
            return $injector->getInstance('IMP_Factory_Imap')->create()->isReadOnly($this);

        case 'search':
            return $injector->getInstance('IMP_Search')->isSearchMbox($this->_mbox);

        case 'special':
            $this->_initCache();

            switch ($this->_mbox) {
            case 'INBOX':
            case self::$_specialCache['draft']:
            case self::$_specialCache['spam']:
            case self::$_specialCache['trash']:
                return true;
            }

            return in_array($this->_mbox, self::$_specialCache['sent']);

        case 'special_outgoing':
            $this->_initCache();
            return (($this->_mbox == self::$_specialCache['draft']) ||
                    in_array($this->_mbox, self::$_specialCache['sent']));

        case 'specialvfolder':
            return !$this->editvfolder;

        case 'sub':
            return $injector->getInstance('IMP_Imap_Tree')->isSubscribed($this->_mbox);

        case 'subfolders':
            $imaptree = $injector->getInstance('IMP_Imap_Tree');
            $imaptree->setIteratorFilter(IMP_Imap_Tree::FLIST_NOCONTAINER | IMP_Imap_Tree::FLIST_UNSUB | IMP_Imap_Tree::FLIST_NOBASE, $this->_mbox);
            return array_merge($this, iterator_to_array($imaptree));

        case 'threadsort':
            /* Thread sort is always available for IMAP servers, since
             * Horde_Imap_Client_Socket has a built-in ORDEREDSUBJECT
             * implementation. We will always prefer REFERENCES, but will
             * fallback to ORDEREDSUBJECT if the server doesn't support THREAD
             * sorting. */
            return (($GLOBALS['session']->get('imp', 'protocol') == 'imap') &&
                    !$this->search);

        case 'uidvalid':
            return $injector->getInstance('IMP_Factory_Imap')->create()->checkUidvalidity($this->_mbox);

        case 'value':
            return $this->_mbox;

        case 'vfolder':
            return $injector->getInstance('IMP_Search')->isVFolder($this->_mbox);

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
     * @see IMP_Folder::create().
     *
     * @param array $opts  See IMP_Folder::create().
     *
     * @return boolean  True on success.
     * @throws Horde_Exception
     */
    public function create(array $opts = array())
    {
        return ($this->exists ||
                $GLOBALS['injector']->getInstance('IMP_Folder')->create($this->_mbox, $GLOBALS['prefs']->getValue('subscribe'), $opts));
    }

    /**
     * TODO
     */
    public function getListOb($indices = null)
    {
        return $GLOBALS['injector']->getInstance('IMP_Factory_MailboxList')->create($this, $indices);
    }

    /**
     * TODO
     */
    public function getSearchOb()
    {
        $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
        return $imp_search[$this->_mbox];
    }

    /**
     * Return the sorting preference for this mailbox.
     *
     * @param boolean $convert  Convert 'by' to a Horde_Imap_Client constant?
     *
     * @return array  An array with the following keys:
     *   - by: (integer) Sort type.
     *   - dir (integer) Sort direction.
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
        );

        /* Restrict POP3 sorting to sequence only.  Although possible to
         * abstract other sorting methods, all other methods require a
         * download of all messages, which is too much overhead.*/
        if ($GLOBALS['session']->get('imp', 'protocol') == 'pop') {
            $ob['by'] = Horde_Imap_Client::SORT_SEQUENCE;
            return $ob;
        }

        switch ($ob['by']) {
        case Horde_Imap_Client::SORT_THREAD:
            /* Can't do threaded searches in search mailboxes. */
            if (!$this->threadsort) {
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
     * @param boolean $force  Force a redetermination of the return value
     *                        (return value is normally cached after the first
     *                        call).
     *
     * @return boolean  True if deleted messages should be hidden.
     */
    public function hideDeletedMsgs($force = false)
    {
        global $injector, $prefs;

        $delhide = isset($this->_cache['delhide'])
            ? $this->_cache['delhide']
            : null;

        if ($force || is_null($delhide)) {
            $use_trash = $prefs->getValue('use_trash');

            if ($use_trash &&
                $this->get($prefs->getValue('trash_folder'))->vtrash) {
                $delhide = !$this->vtrash;
            } elseif ($prefs->getValue('delhide') && !$use_trash) {
                if ($this->search) {
                    $delhide = true;
                } else {
                    $sortpref = $this->getSort();
                    $delhide = ($sortpref['by'] != Horde_Imap_Client::SORT_THREAD);
                }
            } else {
                $delhide = false;
            }
        }

        $this->_cache['delhide'] = $delhide;

        return $delhide;
    }

    /* Static methods. */

    /**
     * Converts a mailbox string from a form representation.
     * Needed because null characters (used for various internal non-IMAP
     * mailbox representations) will not work in form elements.
     *
     * @param string $mbox  The mailbox name.
     *
     * @return IMP_Mailbox  The mailbox object.
     */
    static public function formFrom($mbox)
    {
        return self::get(rawurldecode($mbox));
    }

    /**
     * Converts a mailbox string to a form representation.
     * Needed because null characters (used for various internal non-IMAP
     * mailbox representations) will not work in form elements.
     *
     * @param string $mbox  The mailbox name.
     *
     * @return string  The converted mailbox string.
     */
    static public function formTo($mbox)
    {
        return htmlspecialchars(rawurlencode($mbox), ENT_COMPAT, 'UTF-8');
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
     * @return string  THe value to store in a preference.
     */
    static public function prefTo($mbox)
    {
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
        $def_ns = $imp_imap->defaultNamespace();
        $empty_ns = $imp_imap->getNamespace('');

        if (($ns = $imp_imap->getNamespace($mbox)) !== null) {
             if ($ns['name'] == $def_ns['name']) {
                 /* From personal namespace => strip namespace. */
                 return substr($mbox, strlen($def_ns['name']));
             } elseif ($ns['name'] == $empty_ns['name']) {
                 /* From empty namespace => prefix with delimiter. */
                 return $empty_ns['delimiter'] . $mbox;
             }
         }

        return $mbox;
    }

    /* Internal methods. */

    /**
     * Returns a unique identifier for this mailbox's status.
     *
     * This cache ID is guaranteed to change if messages are added/deleted from
     * the mailbox. Additionally, if CONDSTORE is available on the remote
     * IMAP server, this ID will change if flag information changes.
     *
     * @return string  The cache ID string, which will change when the
     *                 composition of this mailbox changes.
     */
    protected function _getCacheID()
    {
        if (!$this->search) {
            $sortpref = $this->getSort(true);
            try {
                return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->getCacheId($this->_mbox, array($sortpref['by'], $sortpref['dir']));
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        return strval(new Horde_Support_Randomid());
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
        global $prefs;

        if (!$notranslate && isset($this->_cache['display'])) {
            return $this->_cache['display'];
        }

        $ns_info = $this->namespace_info;
        $delimiter = is_null($ns_info)
            ? ''
            : $ns_info['delimiter'];

        /* Substitute any translated prefix text. */
        $sub_array = array(
            'INBOX' => _("Inbox"),
            $prefs->getValue('sent_mail_folder') => _("Sent"),
            $prefs->getValue('drafts_folder') => _("Drafts"),
            $prefs->getValue('trash_folder') => _("Trash"),
            $prefs->getValue('spam_folder') => _("Spam")
        );

        /* Strip namespace information. */
        if (!is_null($ns_info) &&
            !empty($ns_info['name']) &&
            ($ns_info['type'] == 'personal') &&
            substr($this->_mbox, 0, strlen($ns_info['name'])) == $ns_info['name']) {
            $out = substr($this->_mbox, strlen($ns_info['name']));
        } else {
            $out = $this->_mbox;
        }

        if ($notranslate) {
            return $out;
        }

        foreach ($sub_array as $key => $val) {
            if ((($key != 'INBOX') || ($this->_mbox == $out)) &&
                stripos($out, $key) === 0) {
                $len = strlen($key);
                if ((strlen($out) == $len) || ($out[$len] == $delimiter)) {
                    $out = substr_replace($out, Horde_String::convertCharset($val, 'UTF-8', 'UTF7-IMAP'), 0, $len);
                    break;
                }
            }
        }

        $this->_cache['display'] = Horde_String::convertCharset($out, 'UTF7-IMAP', 'UTF-8');

        return $this->_cache['display'];
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
        $this->_initCache();

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
            switch ($this->_mbox) {
            case 'INBOX':
                $info->alt = _("Inbox");
                $info->class = 'inboxImg';
                $info->icon = 'folders/inbox.png';
                break;

            case self::$_specialCache['draft']:
                $info->alt = _("Draft folder");
                $info->class = 'draftsImg';
                $info->icon = 'folders/drafts.png';
                break;

            case self::$_specialCache['spam']:
                $info->alt = _("Spam folder");
                $info->class = 'spamImg';
                $info->icon = 'folders/spam.png';
                break;

            case self::$_specialCache['trash']:
                $info->alt = _("Trash folder");
                $info->class = 'trashImg';
                $info->icon = 'folders/trash.png';
                break;

            default:
                if (in_array($this->_mbox, self::$_specialCache['sent'])) {
                    $info->alt = _("Sent mail folder");
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
        if (!isset($this->_cache['icons'])) {
            try {
                $this->_cache['icons'] = Horde::callHook('mbox_icons', array(), 'imp');
            } catch (Horde_Exception_HookNotSet $e) {
                $this->_cache['icons'] = array();
            }
        }

        if (isset($this->_cache['icons'][$this->_mbox])) {
            $mi = $this->_cache['icons'][$this->_mbox];

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
     * Init frequently used data.
     */
    protected function _initCache()
    {
        if (!self::$_specialCache) {
            self::$_specialCache = $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->getSpecialMailboxes();
        }
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return $this->_mbox;
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_mbox = $data;
    }

}
