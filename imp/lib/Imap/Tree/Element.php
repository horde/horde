<?php
/**
 * The IMP_Imap_Tree_Element class provides a data structure for storing
 * information about a mailbox.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Imap_Tree_Element
{
    /**
     * A mailbox element array.
     *
     * @see IMP_Imap_Tree#_makeElt().
     *
     * @var array
     */
    protected $_mbox;

    /**
     * A reference to the IMP_Imap_Tree object.
     *
     * @var IMP_Imap_Tree
     */
    protected $_treeob;

    /**
     * Element cache.
     *
     * @var array
     */
    static protected $_eltCache;

    /**
     * Mailbox icons cache.
     *
     * @var array
     */
    static protected $_mboxIcons;

    /**
     * Constructor.
     *
     * @var array $mbox            A mailbox element array.
     * @var IMP_Imap_Tree $treeob  A tree object.
     */
    public function __construct($mbox, $treeob)
    {
        $this->_mbox = $mbox;
        $this->_treeob = $treeob;
    }

    /**
     * Return information on a mailbox.
     *
     * @param string $key  The data item to return.
     * <pre>
     * 'base_elt' - (array) A mailbox element array. See IMP_Imap_Tree#get().
     * 'children' - (boolean) Does the element have children?
     * 'container' - (boolean) Is this a container element?
     * 'display' - (string) The mailbox name run through IMP::displayFolder().
     * 'editvfolder' - (boolean) Can this virtual folder be edited?
     * 'is_open' - (boolean) Is this level expanded?
     * 'icon' - (object) Icon information for the mailbox. Properties:
     *   'alt' - (string) The alt text for the icon.
     *   'class' - (string) The CSS class name.
     *   'icon' - (Horde_Themes_Image) The icon graphic to use.
     *   'iconopen' - (Horde_Themes_Image) The openicon to use.
     *   'user_icon' - (boolean) Use a user defined icon?
     * 'invisible' - (boolean) Is element invisible?
     * 'label' - (string) The mailbox name run through IMP::getLabel().
     *           Does NOT include full mailbox path.
     * 'level' - (integer) The deepness level of this element.
     * 'mbox_val' - (string) A html-ized version of 'value'.
     * 'name' - (string) A html-ized version of 'label'.
     * 'namespace' - (string) Is this a namespace element?
     * 'nonimap' - (boolean) Is this a non-IMAP element?
     * 'parent' - (array) The parent element value.
     * 'polled' - (boolean) Show polled information?
     * 'poll_info' - (object) Poll information for the mailbox. Properties:
     *   'msgs' - (integer) The number of total messages in the element (if
     *            polled).
     *   'recent' - (integer) The number of new messages in the element (if
     *              polled).
     *   'unseen' - (integer) The number of unseen messages in the element (if
     *              polled).
     * 'sub' - (boolean) Is folder subscribed to?
     * 'special' - (boolean) Is this is a "special" element?
     * 'specialvfolder' - (boolean) Is this a "special" virtual folder?
     * 'value' - (string) The value of this element (i.e. element id).
     * 'vfolder' - (boolean) Is this a virtual folder?
     * </pre>
     *
     * @return mixed  The information.
     */
    public function __get($key)
    {
        switch ($key) {
        case 'base_elt':
            return $this->_mbox;

        case 'children':
            return $this->_treeob->hasChildren($this->_mbox);

        case 'container':
            return $this->_treeob->isContainer($this->_mbox);

        case 'display':
            return $this->nonimap
                ? $this->label
                : IMP::displayFolder($this->value);

        case 'editvfolder':
            return $GLOBALS['injector']->getInstance('IMP_Search')->isVFolder($this->value, true);

        case 'is_open':
            return $this->_treeob->isOpen($this->_mbox);

        case 'icon':
            return $this->_getIcon();

        case 'invisible':
            return $this->_treeob->isInvisible($this->_mbox);

        case 'label':
            return $this->_mbox['l'];

        case 'level':
            return $this->_mbox['c'];

        case 'mbox_val':
            return htmlspecialchars($this->value);

        case 'name':
            return htmlspecialchars($this->label);

        case 'namespace':
            return $this->_treeob->isNamespace($this->_mbox);

        case 'nonimap':
            return $this->_treeob->isNonImapElt($this->_mbox);

        case 'parent':
            return $this->_mbox['p'];

        case 'poll_info':
            $info = new stdClass;
            $info->msgs = 0;
            $info->recent = 0;
            $info->unseen = 0;

            try {
                if ($msgs_info = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->status($this->value, Horde_Imap_Client::STATUS_RECENT | Horde_Imap_Client::STATUS_UNSEEN | Horde_Imap_Client::STATUS_MESSAGES)) {
                    if (!empty($msgs_info['recent'])) {
                        $info->recent = intval($msgs_info['recent']);
                    }
                    $info->msgs = intval($msgs_info['messages']);
                    $info->unseen = intval($msgs_info['unseen']);
                }
            } catch (Horde_Imap_Client_Exception $e) {}

            return $info;

        case 'polled':
            return $this->_treeob->isPolled($this->_mbox);

        case 'special':
            $this->_initCache();

            switch ($this->value) {
            case 'INBOX':
            case $this->_eltCache['draft']:
            case $this->_eltCache['spam']:
            case $this->_eltCache['trash']:
                return true;

            default:
                return in_array($this->value, $this->_eltCache['sent']);
            }

            return false;

        case 'specialvfolder':
            return !$GLOBALS['injector']->getInstance('IMP_Search')->isVFolder($this->value, true);

        case 'sub':
            return $this->_treeob->isSubscribed($this->_mbox);

        case 'value':
            return $this->_mbox['v'];

        case 'vfolder':
            return $this->_treeob->isVFolder($this->_mbox);
        }

        return false;
    }

    /**
     * Return icon information.
     *
     * @return stdClass  TODO
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
            switch ($this->_mbox['v']) {
            case 'INBOX':
                $info->alt = _("Inbox");
                $info->class = 'inboxImg';
                $info->icon = 'folders/inbox.png';
                break;

            case $this->_eltCache['trash']:
                $info->alt = _("Trash folder");
                $info->class = 'trashImg';
                $info->icon = 'folders/trash.png';
                break;

            case $this->_eltCache['draft']:
                $info->alt = _("Draft folder");
                $info->class = 'draftsImg';
                $info->icon = 'folders/drafts.png';
                break;

            case $this->_eltCache['spam']:
                $info->alt = _("Spam folder");
                $info->class = 'spamImg';
                $info->icon = 'folders/spam.png';
                break;

            default:
                if (in_array($this->_mbox['v'], $this->_eltCache['sent'])) {
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
                if ($imp_search->isVTrash($this->_mbox['v'])) {
                    $info->alt = $imp_search[$this->_mbox['v']]->label;
                    $info->class = 'trashImg';
                    $info->icon = 'folders/trash.png';
                } elseif ($imp_search->isVinbox($this->_mbox['v'])) {
                    $info->alt = $imp_search[$this->_mbox['v']]->label;
                    $info->class = 'inboxImg';
                    $info->icon = 'folders/inbox.png';
                }
            }
        }

        /* Overwrite the icon information now. */
        if (!isset($this->_mboxIcons)) {
            try {
                $this->_mboxIcons = Horde::callHook('mbox_icons', array(), 'imp');
            } catch (Horde_Exception_HookNotSet $e) {
                $this->_mboxIcons = array();
            }
        }

        if (isset($this->_mboxIcons[$this->_mbox['v']])) {
            $mi = $this->_mboxIcons[$this->_mbox['v']];

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
     * Init frequently used element() data.
     */
    protected function _initCache()
    {
        if (!isset($this->_eltCache)) {
            $this->_eltCache = $this->_treeob->getSpecialMailboxes();
        }
    }

}
