<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This object manages IMP_Mailbox cache data that is stored in the session.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read boolean $changed  Has the internal object storage changed?
 */
class IMP_Mailbox_SessionCache implements Serializable
{
    /** Changed constants. */
    const CHANGED_NO = 0;
    const CHANGED_YES = 1;

    /** Cache identifiers. */
    const CACHE_ACL = 'a'; // (array) ACL rights
    const CACHE_DISPLAY = 'd'; // (string) Display string
    const CACHE_ICONS = 'i'; // (array) Icons array
    const CACHE_LABEL = 'l'; // (string) Label string
    const CACHE_UIDVALIDITY = 'v'; // (integer) UIDVALIDITY

    /** Cache identifiers - temporary data. */
    const CACHE_ICONHOOK = 'ic';
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
     * Temporary (access-only) cached data.
     *
     * @var array
     */
    protected $_temp = array();

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'changed':
            return $this->_changed;
        }
    }

    /**
     * Get ACL data.
     *
     * @param string $mbox  Mailbox.
     *
     * @return mixed  A Horde_Imap_Client_Data_Acl object, null if no ACL
     *                data, or false if not set.
     */
    public function getAcl($mbox)
    {
        if (!isset($this->_cache[$mbox][self::CACHE_ACL])) {
            return false;
        }

        return is_null($this->_cache[$mbox][self::CACHE_ACL])
            ? null
            : new Horde_Imap_Client_Data_Acl($this->_cache[$mbox][self::CACHE_ACL]);
    }

    /**
     * Set ACL data.
     *
     * @param string $mbox  Mailbox.
     * @param mixed $acl    Horde_Imap_Client_Data_Acl object, or null.
     */
    public function setAcl($mbox, $acl)
    {
        /* Store string representation of ACL for a more compact
         * serialized format. */
        $this->_cache[$mbox][self::CACHE_ACL] = strval($acl);
        $this->_changed = self::CHANGED_YES;
    }

    /**
     * Get the mailbox label.
     *
     * @param string $mbox  Mailbox.
     *
     * @return mixed  Mailbox label, or false if not set.
     */
    public function getLabel($mbox)
    {
        if (!isset($this->_cache[$mbox][self::CACHE_LABEL])) {
            return false;
        }

        return ($this->_cache[$mbox][self::CACHE_LABEL] === true)
            ? $this->_cache[$mbox][self::CACHE_DISPLAY]
            : $this->_cache[$mbox][self::CACHE_LABEL];
    }

    /**
     * Set the mailbox label.
     *
     * @param string $mbox   Mailbox.
     * @param string $label  Label.
     */
    public function setLabel($mbox, $label)
    {
        $this->_cache[$mbox][self::CACHE_LABEL] = (isset($this->_cache[$mbox][self::CACHE_DISPLAY]) && ($this->_cache[$mbox][self::CACHE_DISPLAY] == $label))
            ? true
            : $label;
        $this->_changed = self::CHANGED_YES;
    }

    /**
     * Preference storage name.
     *
     * @param string $mbox  Mailbox.
     *
     * @return mixed  Preference string, or false if not set.
     */
    public function getPrefTo($mbox)
    {
        return isset($this->_temp[$mbox][self::CACHE_PREFTO])
            ? $this->_temp[$mbox][self::CACHE_PREFTO]
            : false;
    }

    /**
     * Set the preference storage name.
     *
     * @param string $mbox     Mailbox.
     * @param string $pref_to  Preference storage name.
     */
    public function setPrefTo($mbox, $pref_to)
    {
        $this->_temp[$mbox][self::CACHE_PREFTO] = $pref_to;
        $this->_changed = self::CHANGED_YES;
    }

    /**
     * Get the UIDVALIDITY of the mailbox.
     *
     * @param string $mbox  Mailbox.
     *
     * @return mixed  UIDVALIDITY, or false if not set.
     */
    public function getUidvalidity($mbox)
    {
        return isset($this->_cache[$mbox][self::CACHE_UIDVALIDITY])
            ? $this->_cache[$mbox][self::CACHE_UIDVALIDITY]
            : false;
    }

    /**
     * Set the UIDVALIDITY of the mailbox.
     *
     * @param string $mbox       Mailbox.
     * @param integer $uidvalid  UIDVALIDITY.
     */
    public function setUidvalidity($mbox, $uidvalid)
    {
        $this->_cache[$mbox][self::CACHE_UIDVALIDITY] = $uidvalid;
        $this->_changed = self::CHANGED_YES;
    }

    /**
     * Get mailbox display label.
     *
     * @param string $mbox  Mailbox.
     *
     * @return mixed  Mailbox display label, or false if not set.
     */
    public function getDisplay($mbox)
    {
        return isset($this->_cache[$mbox][self::CACHE_DISPLAY])
            ? $this->_cache[$mbox][self::CACHE_DISPLAY]
            : false;
    }

    /**
     * Set mailbox display label.
     *
     * @param string $mbox     Mailbox.
     * @param string $display  Display label.
     */
    public function setDisplay($mbox, $display)
    {
        $this->_cache[$mbox][self::CACHE_DISPLAY] = $display;
        $this->_changed = self::CHANGED_YES;
    }

    /**
     * Get icon information.
     *
     * @param string $mbox  Mailbox.
     *
     * @return mixed  Icon information (array), or false if not set.
     */
    public function getIcons($mbox)
    {
        global $injector;

        if (isset($this->_cache[$mbox][self::CACHE_ICONS])) {
            return $this->_cache[$mbox][self::CACHE_ICONS];
        }

        if (!isset($this->_temp[self::CACHE_ICONHOOK])) {
            try {
                $this->_temp[self::CACHE_ICONHOOK] = $injector->getInstance('Horde_Core_Hooks')->callHook('mbox_icons', 'imp');
            } catch (Horde_Exception_HookNotSet $e) {
                $this->_temp[self::CACHE_ICONHOOK] = array();
            }
        }

        $icons = isset($this->_temp[self::CACHE_ICONHOOK][$mbox])
            ? $this->_temp[self::CACHE_ICONHOOK][$mbox]
            : false;

        $this->_cache[$mbox][self::CACHE_ICONS] = $icons;
        $this->_changed = self::CHANGED_YES;

        return $icons;
    }

    /**
     * Return the list of special mailboxes.
     *
     * @return array  A list of mailboxes, with the SPECIAL_* constants
     *                as keys and values containing the IMP_Mailbox objects or
     *                null if the mailbox doesn't exist (SPECIAL_SENT
     *                contains an array of objects).
     */
    public function getSpecialMailboxes()
    {
        global $injector, $prefs;

        if (!isset($this->_temp[self::CACHE_SPECIALMBOXES])) {
            $sm = array(
                IMP_Mailbox::SPECIAL_COMPOSETEMPLATES => IMP_Mailbox::getPref(IMP_Mailbox::MBOX_TEMPLATES),
                IMP_Mailbox::SPECIAL_DRAFTS => IMP_Mailbox::getPref(IMP_Mailbox::MBOX_DRAFTS),
                IMP_Mailbox::SPECIAL_SENT => $injector->getInstance('IMP_Identity')->getAllSentmail(),
                IMP_Mailbox::SPECIAL_SPAM => IMP_Mailbox::getPref(IMP_Mailbox::MBOX_SPAM),
                IMP_Mailbox::SPECIAL_TRASH => $prefs->getValue('use_trash') ? IMP_Mailbox::getPref(IMP_Mailbox::MBOX_TRASH) : null,
                IMP_Mailbox::SPECIAL_USER => array()
            );

            foreach ($injector->getInstance('IMP_Factory_Imap')->create()->config->user_special_mboxes as $key => $val) {
                $ob = IMP_Mailbox::get($key);
                $ob->display = $val;
                $sm[IMP_Mailbox::SPECIAL_USER][strval($key)] = $ob;
            }

            $this->_temp[self::CACHE_SPECIALMBOXES] = $sm;
        }

        return $this->_temp[self::CACHE_SPECIALMBOXES];
    }

    /**
     * Expire cache entries.
     *
     * @param mixed $entries     A CACHE_* constant (or array of constants).
     *                           If null, expires all mailbox-level cache
     *                           entries.
     * @param IMP_Mailbox $mbox  If set, only clear this mailbox's cache.
     */
    public function expire($entries, $mbox = null)
    {
        if (is_null($entries)) {
            $entries = array(
                self::CACHE_ACL,
                self::CACHE_DISPLAY,
                self::CACHE_ICONS,
                self::CACHE_LABEL,
                self::CACHE_PREFTO,
                self::CACHE_UIDVALIDITY
            );
        }

        foreach ((is_array($entries) ? $entries : array($entries)) as $val) {
            switch ($val) {
            case self::CACHE_ACL:
            case self::CACHE_DISPLAY:
            case self::CACHE_ICONS:
            case self::CACHE_LABEL:
            case self::CACHE_PREFTO:
            case self::CACHE_UIDVALIDITY:
                if ($mbox) {
                    $mbox = strval($mbox);
                    if (isset($this->_cache[$val][$mbox]) ||
                        isset($this->_temp[$val][$mbox])) {
                        $this->_changed = self::CHANGED_YES;
                        unset(
                            $this->_cache[$val][$mbox],
                            $this->_temp[$val][$mbox]
                        );
                    }
                    break;
                }
                // Fall-through

            case self::CACHE_ICONHOOK:
            case self::CACHE_SPECIALMBOXES:
                if (isset($this->_cache[$val]) || isset($this->_temp[$val])) {
                    $this->_changed = self::CHANGED_YES;
                    unset($this->_cache[$val], $this->_temp[$val]);
                }
                break;
            }
        }
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return $GLOBALS['injector']->getInstance('Horde_Pack')->pack(
            $this->_cache,
            array(
                'compression' => false,
                'phpob' => false
            )
        );
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_cache = $GLOBALS['injector']->getInstance('Horde_Pack')->unpack($data);
    }

}
