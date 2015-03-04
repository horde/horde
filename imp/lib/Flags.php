<?php
/**
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Provides an interface to deal with display of flags/keywords/labels on
 * messages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Flags implements ArrayAccess, Serializable
{
    /**
     * Has the object data changed?
     *
     * @var boolean
     */
    public $changed = false;

    /**
     * Does the msglist_flags hook exist?
     *
     * @var boolean
     */
    protected $_flaghook = true;

    /**
     * The list of internal flags.
     *
     * @var array
     */
    protected $_flags = array();

    /**
     * The list of user flags.
     *
     * @var array
     */
    protected $_userflags = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        /* Build list of default flags. */
        foreach (array('Imap', 'System') as $type) {
            $di = new DirectoryIterator(IMP_BASE . '/lib/Flag/' . $type);
            foreach ($di as $val) {
                if ($val->isFile()) {
                    $cname = 'IMP_Flag_' . $type . '_' . $val->getBasename('.php');
                    if (class_exists($cname)) {
                        $ob = new $cname();
                        $this->_flags[$ob->id] = $ob;
                    }
                }
            }
        }

        if ($f_list = $GLOBALS['prefs']->getValue('msgflags')) {
            $f_list = @unserialize($f_list);
            if (is_array($f_list)) {
                foreach ($f_list as $val) {
                    $this->_userflags[$val->id] = $val;
                }
            }
        }

        $this->changed = true;
    }

    /**
     * Save the flag list to the prefs backend.
     */
    protected function _save()
    {
        global $prefs;

        if (!$prefs->isLocked('msgflags')) {
            $prefs->setValue('msgflags', serialize($this->_userflags));
        }

        $this->changed = true;
    }

    /**
     * Return the raw list of flags.
     *
     * @param array $opts  Additional options:
     *   - imap: (boolean) If true, only return IMAP flags that can be set by
     *           the user.
     *            DEFAULT: false
     *   - mailbox: (string) A real (not virtual) IMAP mailbox. If set, will
     *              determine what flags are available in the mailbox.
     *              DEFAULT: '' (no mailbox check)
     *
     * @return array  An array of IMP_Flag_Base elements.
     */
    public function getList(array $opts = array())
    {
        if (!$GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FLAGS)) {
            return array();
        }

        $ret = array_merge($this->_flags, $this->_userflags);

        if (!empty($opts['imap'])) {
            foreach ($ret as $key => $val) {
                if (!($val instanceof IMP_Flag_Imap)) {
                    unset($ret[$key]);
                }
            }
        }

        if (!isset($opts['mailbox']) ||
            !strlen($opts['mailbox']) ||
            IMP_Mailbox::get($opts['mailbox'])->search) {
            return array_values($ret);
        }

        /* Alter the list of flags for a mailbox depending on the mailbox's
         * PERMANENTFLAGS status. */
        $permflags = IMP_Mailbox::get($opts['mailbox'])->permflags;

        /* Limited flags allowed in mailbox. */
        foreach ($ret as $key => $val) {
            if (($val instanceof IMP_Flag_Imap) &&
                !$permflags->allowed($val->imapflag)) {
                unset($ret[$key]);
            }
        }

        /* Get list of unknown flags. */
        if ($GLOBALS['prefs']->getValue('show_all_flags')) {
            /* Get list of IMAP flags. */
            $imapflags = array();
            foreach ($ret as $val) {
                if ($val instanceof IMP_Flag_Imap) {
                    $imapflags[] = $val->imapflag;
                }
            }

            foreach ($permflags as $val) {
                if (!in_array($val, $imapflags)) {
                    $ret[] = new IMP_Flag_User($val);
                }
            }
        }

        return array_values($ret);
    }

    /**
     * Add a user-defined IMAP flag.
     *
     * @param string $label  The label to use for the new flag.
     *
     * @return string  The IMAP flag name.
     * @throws IMP_Exception
     */
    public function addFlag($label)
    {
        if (strlen($label) === 0) {
            throw new IMP_Exception(_("Flag name must not be empty."));
        }

        $ob = new IMP_Flag_User($label);

        if (isset($this->_userflags[$ob->id])) {
            throw new IMP_Exception(_("Flag name already exists."));
        }

        $this->_userflags[$ob->id] = $ob;
        $this->_save();

        return $ob->imapflag;
    }

    /**
     * Updates flag properties.
     *
     * @param string $key   The flag key.
     * @param string $type  The property to update. Either 'bgcolor' or
     *                      'label'.
     * @param string $data  The updated data.
     */
    public function updateFlag($key, $type, $data)
    {
        if (isset($this->_userflags[$key])) {
            $ob = clone $this->_userflags[$key];
        } elseif (isset($this->_flags[$key])) {
            $ob = clone $this->_flags[$key];
        } else {
            return;
        }

        $ob->$type = $data;

        if (isset($this->_flags[$key]) && ($this->_flags[$key] == $ob)) {
            unset($this->_userflags[$key]);
        } else {
            $this->_userflags[$key] = $ob;
        }

        $this->_save();
    }

    /**
     * Parse a list of flag information.
     *
     * @param array $opts  Options:
     *   - flags: (array) IMAP flag info. A lowercase list of flags returned
     *            by the IMAP server.
     *   - headers: (Horde_Mime_Headers) Determines message information
     *              from a headers object.
     *   - runhook: (array) Run the msglist_flags hook? If yes, input is
     *              return from IMP_Mailbox_List#getMailboxArray().
     *   - personal: (mixed) Personal message info. Either a list of To
     *               addresses (Horde_Mail_Rfc822_List object) or the identity
     *               that matched the address list.
     *   - structure: TODO
     *
     * @return array  A list of IMP_Flag_Base objects.
     */
    public function parse(array $opts = array())
    {
        global $injector;

        $opts = array_merge(array(
            'flags' => array(),
            'headers' => null,
            'personal' => null,
            'structure' => null
        ), $opts);

        if (!empty($opts['runhook']) && $this->_flaghook) {
            try {
                $opts['flags'] = array_merge(
                    $opts['flags'],
                    $injector->getInstance('Horde_Core_Hooks')->callHook(
                        'msglist_flags',
                        'imp',
                        array($opts['runhook'])
                    )
                );
            } catch (Horde_Exception_HookNotSet $e) {
                $this->_flaghook = false;
            }
        }

        $ret = array();

        foreach (array_merge($this->_flags, $this->_userflags) as $val) {
            if ($val instanceof IMP_Flag_Match_Order) {
                $match = $val->matchOrder();
            } else {
                $match = array(
                    'IMP_Flag_Match_Address',
                    'IMP_Flag_Match_Flag',
                    'IMP_Flag_Match_Header',
                    'IMP_Flag_Match_Structure'
                );
            }

            foreach ($match as $val2) {
                if (!($val instanceof $val2)) {
                    continue;
                }

                $res = null;

                switch ($val2) {
                case 'IMP_Flag_Match_Address':
                    if (!is_null($opts['personal'])) {
                        $res = $val->matchAddress($opts['personal']);
                    }
                    break;

                case 'IMP_Flag_Match_Flag':
                    $res = $val->matchFlag($opts['flags']);
                    break;

                case 'IMP_Flag_Match_Header':
                    if (!is_null($opts['headers'])) {
                        $res = $val->matchHeader($opts['headers']);
                    }
                    break;

                case 'IMP_Flag_Match_Structure':
                    if (!is_null($opts['structure'])) {
                        $res = $val->matchStructure($opts['structure']);
                    }
                    break;
                }

                if (is_bool($res)) {
                    if ($res) {
                        $ret[] = $val;
                    }
                    break;
                }
            }
        }

        return $ret;
    }

    /**
     * Returns a list of flags that have changed due to IMAP flag changes.
     *
     * @param array $flags  The list of IMAP flags added/removed.
     * @param boolean $add  True if these flags were added, false if they were
     *                      removed.
     *
     * @return array  Array with two keys: 'add' and 'remove'. Each key
     *                contains a list of IMP_Flag_Base objects.
     */
    public function changed($flags, $add)
    {
        $ret = array(
            'add' => array(),
            'remove' => array()
        );

        $obs = array();
        foreach ($flags as $val) {
            if ($tmp = $this[$val]) {
                $obs[] = $tmp;
            }
        }

        if ($add) {
            $ret['add'] = $obs;
        } else {
            $ret['remove'] = $obs;
        }

        foreach (array_merge($this->_flags, $this->_userflags) as $val) {
            $res = $val->changed($obs, $add);

            if ($res === false) {
                $ret['remove'][] = $val;
            } elseif ($res === true) {
                $ret['add'][] = $val;
            }
        }

        return $ret;
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset)
    {
        return isset($this->_flags[$offset]) ||
               isset($this->_userflags[$offset]);
    }

    /**
     */
    public function offsetGet($offset)
    {
        if (isset($this->_flags[$offset])) {
            return $this->_flags[$offset];
        } elseif (isset($this->_userflags[$offset])) {
            return $this->_userflags[$offset];
        }

        return null;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function offsetSet($offset, $value)
    {
        throw new InvalidArgumentException('Use addFlag()/updateFlag()');
    }

    /**
     */
    public function offsetUnset($offset)
    {
        if (isset($this->_userflags[$offset])) {
            unset($this->_userflags[$offset]);
            $this->_save();
        }
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return $GLOBALS['injector']->getInstance('Horde_Pack')->pack(
            array(
                $this->_flags,
                $this->_userflags
            ), array(
                'compression' => false,
                'phpob' => true
            )
        );
    }

    /**
     */
    public function unserialize($data)
    {
        list(
            $this->_flags,
            $this->_userflags
        ) = $GLOBALS['injector']->getInstance('Horde_Pack')->unpack($data);
    }

}
