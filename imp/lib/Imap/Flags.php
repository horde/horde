<?php
/**
 * The IMP_Imap_Flags class provides an interface to deal with display of
 * flags/labels on messages.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Imap_Flags
{
    /* IMAP flag prefix for IMP-specific flags/keywords. */
    const PREFIX = 'impflag';

    /**
     * The cached list of flags.
     *
     * @var array
     */
    protected $_flags = null;

    /**
     * The 'msgflags_user' preference value.
     *
     * @var array
     */
    protected $_userflags = null;

    /**
     * Save the flag list to the prefs backend.
     *
     * @param boolean $user  If true, update the user flag list. Otherwise,
     *                       update the system flag list.
     */
    protected function _save($user = true)
    {
        global $prefs;

        if ($user) {
            if (!$prefs->isLocked('msgflags_user')) {
                $prefs->setValue('msgflags_user', json_encode($this->_userflags));
            }
        } elseif (!$prefs->isLocked('msgflags')) {
            $prefs->setValue('msgflags', json_encode(array_diff_key($this->_flags, $this->_userflags)));
        }
    }

    /**
     * Return the raw list of flags.
     *
     * @param array $options  Additional options:
     * <pre>
     * 'div' - (boolean) If true, return a DIV tag containing the code
     *         necessary to display the icon.
     *         DEFAULT: false
     * 'fgcolor' - (boolean) If true, add foreground color information to be
     *             used for text overlay purposes.
     *             DEFAULT: false
     * 'imap' - (boolean) If true, only return IMAP flags that can be set by
     *          the user.
     *          DEFAULT: false
     * 'mailbox' - (string) A real (not virtual) IMAP mailbox. If set, will
     *             determine what flags are available in the mailbox.
     *             DEFAULT: '' (no mailbox check)
     * </pre>
     *
     * @return array  An array of flag information (see 'msgflags' preference
     *                for format). If 'fgcolor' option is true, also adds
     *                a 'f' key to each entry with foreground color info.
     *                If 'div' option is true, adds a 'div' key with HTML
     *                text.
     */
    public function getList($options = array())
    {
        $this->_loadList();

        $avail_flags = array_keys($this->_flags);

        $ret = $types = array();
        if (!empty($options['imap'])) {
            $types = array('imapp', 'imapu');
        }

        /* Reduce the list of flags for the mailbox depending on the return
         * from the PERMANENTFLAGS IMAP response. */
        if (!empty($options['mailbox'])) {
            try {
                /* Make sure we are in R/W mailbox mode (SELECT). No flags are
                 * allowed in EXAMINE mode. */
                $GLOBALS['imp_imap']->ob()->openMailbox($options['mailbox'], Horde_Imap_Client::OPEN_READWRITE);
                $status = $GLOBALS['imp_imap']->ob()->status($options['mailbox'], Horde_Imap_Client::STATUS_PERMFLAGS);
                if (!in_array('\\*', $status['permflags'])) {
                    $avail_flags = array_intersect($avail_flags, $status['permflags']);
                }
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        foreach ($avail_flags as $key) {
            $ret[$key] = $this->_flags[$key];
            $ret[$key]['flag'] = $key;

            if (!empty($options['fgcolor'])) {
                $ret[$key] = $this->_getColor($key, $ret[$key]);
            }

            if (!empty($options['imap']) &&
                !in_array($ret[$key]['t'], $types)) {
                unset($ret[$key]);
            } elseif (!empty($options['div']) && isset($ret[$key]['c'])) {
                $ret[$key]['div'] = $this->_getDiv($ret[$key]['c'], $ret[$key]['l']);
            }
        }

        return $ret;
    }

    /**
     * Loads the flag list from the preferences into the local cache.
     */
    protected function _loadList()
    {
        if (!is_null($this->_flags)) {
            return;
        }

        $this->_userflags = json_decode($GLOBALS['prefs']->getValue('msgflags_user'), true);
        $this->_flags = array_merge(
            $this->_userflags,
            json_decode($GLOBALS['prefs']->getValue('msgflags'), true)
        );

        /* Sanity checking. */
        if (is_array($this->_flags)) {
            $this->_flags = array_change_key_case($this->_flags, CASE_LOWER);
        } else {
            $this->_flags = array();
            $this->_save();
        }
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

        $this->_loadList();

        /* Flags are named PREFIX{$i}. Keep incrementing until we find the
         * next available flag. */
        for ($i = 0;; ++$i) {
            $curr = self::PREFIX . $i;
            if (!isset($this->_flags[$curr])) {
                $entry = array(
                    // 'a' => These flags are not shown in mimp
                    // TODO: Generate random background
                    'b' => '#ffffff',
                    'c' => 'flagUser',
                    'd' => true,
                    'l' => $label,
                    't' => 'imapp'
                );

                $this->_flags[$curr] = $entry;
                $this->_userflags[$curr] = $entry;

                $this->_save();
                return $curr;
            }
        }
    }

    /**
     * Updates a flag.
     *
     * @param string $label  The flag label.
     * @param array $data    The data to update.
     */
    public function updateFlag($label, $data)
    {
        $this->_loadList();

        if (isset($this->_flags[$label])) {
            foreach ($data as $key => $val) {
                $this->_flags[$label][$key] = $val;
            }

            $this->_save(isset($this->_updateflags[$label]));
        }
    }

    /**
     * Delete a flag from the list.
     *
     * @param string $label  The flag label.
     *
     * @return boolean  True on success.
     */
    public function deleteFlag($label)
    {
        $this->_loadList();

        if (isset($this->_flags[$label]) &&
            $this->_flags[$label]['l'] &&
            !empty($this->_flags[$label]['d'])) {
            unset($this->_flags[$label], $this->_userflags[$label]);
            $this->_save();
            return true;
        }

        return false;
    }

    /**
     * Parse a list of flag information.
     *
     * @param array $options  Additional options:
     * <pre>
     * 'atc' - (Horde_Mime_Part) Attachment info. A Horde_Mime_Part object
     *         representing the message structure.
     *         DEFAULT: not parsed
     * 'div' - (boolean) If true, return a DIV tag containing the code
     *         necessary to display the icon.
     *         DEFAULT: false
     * 'flags' - (array) [REQUIRED] IMAP flag info. A lowercase list of flags
     *           returned by the IMAP server.
     * 'personal' - (mixed) Personal message info. Either an array of to
     *              addresses as returned by
     *              Horde_Mime_Address::getAddressesFromObject(), or the
     *              identity that matched the address list..
     * 'priority' - (Horde_Mime_Headers) Determines priority information from
     *              a headers object.
     * </pre>
     *
     * @return array  A list of flags with the following keys:
     * <pre>
     * 'abbrev' - (string) The abbreviation to use.
     * 'bg' - (string) The background to use.
     * 'classname' - (string) If set, the flag classname to use.
     * 'fg' - (string) The foreground color to use.
     * 'flag' - (string) The matched flag (lowercase).
     * 'div' - (string) A DIV HTML element, if 'div' option is true and a
     *         classname is defined.
     * 'label' - (string) The label of the flag.
     * 'type' - (string) The flag type.
     * </pre>
     */
    public function parse($options = array())
    {
        $this->_loadList();

        $process = $ret = array();
        $f = $this->_flags;

        if (isset($options['personal'])) {
            if (is_array($options['personal'])) {
                $identity = $GLOBALS['injector']->getInstance('IMP_Identity');
                foreach ($options['personal'] as $val) {
                    if ($identity->hasAddress($val['inner'])) {
                        $process['personal'] = $f['personal'];
                        break;
                    }
                }
            } else if (!is_null($options['personal'])) {
                $process['personal'] = $f['personal'];
            }
        }

        if (!empty($options['priority'])) {
            $imp_hdr_ui = new IMP_Ui_Headers();
            switch ($imp_hdr_ui->getPriority($options['priority'])) {
            case 'high':
                $process['highpri'] = $f['highpri'];
                break;

            case 'low':
                $process['lowpri'] = $f['lowpri'];
                break;
            }
        }

        if (!empty($options['atc'])) {
            $imp_mbox_ui = new IMP_Ui_Mailbox();
            if ($type = $imp_mbox_ui->getAttachmentType($options['atc']->getType())) {
                $process[$type] = $f[$type];
            }
        }

        if (($_SESSION['imp']['protocol'] == 'imap') &&
            isset($options['flags'])) {
            if (!empty($options['flags'])) {
                $options['flags'] = array_map('strtolower', $options['flags']);
            }

            foreach ($f as $k => $v) {
                if (in_array($v['t'], array('imap', 'imapp', 'imapu', 'imp'))) {
                    if (empty($v['n'])) {
                        $match = in_array($k, $options['flags']);
                    } elseif (empty($options['flags'])) {
                        $match = true;
                    } else {
                        $match = !in_array($k, $options['flags']);
                    }

                    if ($match) {
                        $process[$k] = $v;
                    }
                }
            }
        }

        foreach ($process as $key => $val) {
            $color = $this->_getColor($key, $val);

            $tmp = array(
                'bg' => $color['b'],
                'fg' => $color['f'],
                'flag' => $key,
                'label' => $val['l'],
                'type' => $val['t']
            );

            if (isset($val['a'])) {
                $tmp['abbrev'] = $val['a'];
            }

            if (isset($val['c'])) {
                $tmp['classname'] = $val['c'];
                if (!empty($options['div'])) {
                    $tmp['div'] = $this->_getDiv($val['c'], $val['l']);
                }
            }

            $ret[] = $tmp;
        }

        return $ret;
    }

    /**
     * Get the list of set/unset actions for use in dropdown lists.
     *
     * @param string $mbox  The current mailbox.
     *
     * @return array  An array with 2 elements: 'set' and 'unset'.
     */
    public function getFlagList($mbox)
    {
        $ret = array('set' => array(), 'unset' => array());

        foreach ($this->getList(array('imap' => true, 'mailbox' => $mbox)) as $val) {
            $tmp = array(
                'f' => $val['flag'],
                'l' => $val['l']
            );

            /* Check for 'opposite' flag actions. */
            $act1 = isset($val['n']) ? 'unset' : 'set';
            $act2 = ($act1 == 'set') ? 'unset' : 'set';

            $ret[$act1][] = $tmp;
            $tmp['f'] = '0\\' . $val['flag'];
            $ret[$act2][] = $tmp;
        }

        return $ret;
    }

    /**
     * Output a DIV element to display the icon.
     *
     * @param string $c  A classname.
     * @param string $l  The flag label.
     *
     * @return string  A HTML DIV element.
     */
    protected function _getDiv($c, $l)
    {
        return '<div class="msgflags ' . $c . '" title="' . htmlspecialchars($l) . '"></div>';
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
        if (strpos($id, '0\\') === 0) {
            return array('flag' => substr($id, 2), 'set' => false);
        }
        return array('flag' => $id, 'set' => true);
    }

    /**
     * Determines the colors for an entry.
     *
     * @param string $key  The flag key.
     * @param array $in    The array of flag data.
     *
     * @return array  $in with the 'b' and 'f' keys populated.
     */
    protected function _getColor($key, $in)
    {
        $in['f'] = '#000';
        if (!isset($in['b'])) {
            $in['b'] = $GLOBALS['prefs']->getValue('msgflags_color');
        } elseif (Horde_Image::brightness($this->_flags[$key]['b']) < 128) {
            $in['f'] = '#f6f6f6';
        }

        return $in;
    }

}
