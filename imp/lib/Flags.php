<?php
/**
 * The IMP_Flags class provides an interface to deal with display of
 * flags/keywords/labels on messages.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
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
     * <pre>
     * 'imap' - (boolean) If true, only return IMAP flags that can be set by
     *          the user.
     *          DEFAULT: false
     * 'mailbox' - (string) A real (not virtual) IMAP mailbox. If set, will
     *             determine what flags are available in the mailbox.
     *             DEFAULT: '' (no mailbox check)
     * </pre>
     *
     * @return array  An array of IMP_Flag_Base elements.
     */
    public function getList(array $opts = array())
    {
        $ret = array_merge($this->_flags, $this->_userflags);

        if (!empty($opts['imap'])) {
            foreach ($ret as $key => $val) {
                if (!($val instanceof IMP_Flag_Imap)) {
                    unset($ret[$key]);
                }
            }
        }

        if (!isset($opts['mailbox']) || !strlen($opts['mailbox'])) {
            return array_values($ret);
        }

        /* Alter the list of flags for a mailbox depending on the return
         * from the PERMANENTFLAGS IMAP response. */
        try {
            /* Make sure we are in R/W mailbox mode (SELECT). No flags are
             * allowed in EXAMINE mode. */
            $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
            $imp_imap->openMailbox($opts['mailbox'], Horde_Imap_Client::OPEN_READWRITE);
            $status = $imp_imap->status($opts['mailbox'], Horde_Imap_Client::STATUS_PERMFLAGS);
        } catch (Horde_Imap_Client_Exception $e) {
            return array_values($ret);
        }

        /* Limited flags allowed in mailbox. */
        if (array_search('\\*', $status['permflags']) === false) {
            foreach ($ret as $key => $val) {
                if (($val instanceof IMP_Flag_Imap) &&
                    !in_array($val->imapflag, $status['permflags'])) {
                    unset($ret[$key]);
                }
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

            foreach ($status['permflags'] as $val) {
                if (($val != '\\*') && !in_array($val, $imapflags)) {
                    $ob = new IMP_Flag_User(Horde_String::convertCharset($val, 'UTF7-IMAP', 'UTF-8'), $val);
                    $ret[] = $ob;
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
     */
    public function addFlag($label)
    {
        if (strlen($label) == 0) {
            return;
        }

        $ob = new IMP_Flag_User($label);

        if (!isset($this->_userflags[$ob->id])) {
            $this->_userflags[$ob->id] = $ob;
            $this->_save();
        }

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
        if (isset($this->_flags[$key])) {
            $ob = $this->_flags[$key];
        } elseif (isset($this->_userflags[$key])) {
            $ob = $this->_userflags[$key];
        } else {
            return;
        }

        $ob->$type = $data;

        if (isset($this->_flags[$key]) && ($this->_flags[$key] == $ob)) {
            if (isset($this->_userflags[$key])) {
                unset($this->_userflags[$key]);
                $this->_save();
            }
        } else {
            $this->_userflags[$key] = $ob;
            $this->_save();
        }
    }

    /**
     * Parse a list of flag information.
     *
     * @param array $opts  Options:
     * <pre>
     * 'flags' - (array) IMAP flag info. A lowercase list of flags returned
     *           by the IMAP server.
     * 'headers' - (Horde_Mime_Headers) Determines attachment and priority
     *             information from a headers object.
     * 'personal' - (mixed) Personal message info. Either an array of To
     *              addresses as returned by
     *              Horde_Mime_Address::getAddressesFromObject() or the
     *              identity that matched the address list.
     * </pre>
     *
     * @return array  A list of IMP_Flag_Base objects.
     */
    public function parse(array $opts = array())
    {
        $opts = array_merge(array(
            'flags' => array(),
            'headers' => null,
            'personal' => null
        ), $opts);

        $imap = ($GLOBALS['session']->get('imp', 'protocol') == 'imap');
        $ret = array();

        foreach (array_merge($this->_flags, $this->_userflags) as $val) {
            switch (get_class($val)) {
            case 'IMP_Flag_System_Attachment':
            case 'IMP_Flag_System_Encrypted':
            case 'IMP_Flag_System_HighPriority':
            case 'IMP_Flag_System_LowPriority':
            case 'IMP_Flag_System_Signed':
                if (!is_null($opts['headers']) &&
                    $val->match($opts['headers'])) {
                    $ret[] = $val;
                }
                break;

            case 'IMP_Flag_System_Personal':
                if (!is_null($opts['personal']) &&
                    $val->match($opts['personal'])) {
                    $ret[] = $val;
                }
                break;

            case 'IMP_Flag_System_Unseen':
            default:
                if ($imap && $val->match($opts['flags'])) {
                    $ret[] = $val;
                }
                break;
            }
        }

        return $ret;
    }

    /**
     * Process a flag ID formatted for use in form data.
     *
     * @param string $id  The ID from form data.
     *
     * @return array  Two element array:
     * <pre>
     * 'flag' - (string) The flag name.
     * 'set' - (boolean) Whether the flag should be set or not.
     * </pre>
     */
    public function parseFormId($id)
    {
        return (strpos($id, '0\\') === 0)
            ? array('flag' => substr($id, 2), 'set' => false)
            : array('flag' => $id, 'set' => true);
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
            $obs[] = $this[$val];
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
        } elseif ($this->_userflags[$offset]) {
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
        return serialize(array(
            $this->_flags,
            $this->_userflags
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data)) {
            throw new Exception('Cache invalidation.');
        }

        $this->_flags = $data[0];
        $this->_userflags = $data[1];
    }

}
