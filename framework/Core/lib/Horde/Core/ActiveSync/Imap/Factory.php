<?php
/**
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2016 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=Core
 * @package   Core
 */

/**
 * Horde_Core_ActiveSync_Imap_Factory implements a factory/builder for
 * providing a Horde_ActiveSync_Imap_Adapter object as well as building a tree
 * of available mailboxes.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2016 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=Core
 * @package   Core
 */
class Horde_Core_ActiveSync_Imap_Factory implements Horde_ActiveSync_Interface_ImapFactory
{
    protected $_adapter;
    protected $_mailboxlist;
    protected $_specialMailboxlist;

    /**
     * Return a Horde_Imap_Client
     *
     * @return Horde_Imap_Client_Base
     * @throws Horde_ActiveSync_Exception
     */
    public function getImapOb()
    {
        if (empty($this->_adapter)) {
            try {
                $this->_adapter = $GLOBALS['registry']->mail->imapOb();
            } catch (Horde_Exception $e) {
                throw new Horde_ActiveSync_Exception($e);
            }
        }

        return $this->_adapter;
    }

    /**
     * Return an array of email folders.
     *
     * @param boolean $force  If true, will force a refresh of the folder list.
     *
     * @return array  An array of folder information. Each entry is keyed by
     *                the mailbox UTF-8 name and contains:
     *                - level: How many parents a folder has, 0 is the root.
     *                - label: The display label for the mailbox.
     *                - d:     The delimiter.
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function getMailboxes($force = false)
    {
        global $registry, $injector;

        if (empty($this->_mailboxlist) || $force) {
            $subscriptions = $registry->horde->getPreference(
                $registry->hasInterface('mail'),
                'subscribe'
            );
            try {
                foreach ($registry->mail->mailboxList(array('reload' => true, 'unsub' => !$subscriptions)) as $mbox) {
                    if (isset($mbox['subscribed'])) {
                        /* IMP 7. Guaranteed that return will match what was
                         * asked for in 'unsub' argument. */
                        if (!$subscriptions || $mbox['subscribed']) {
                            $this->_mailboxlist[$mbox['ob']->utf8] = $mbox;
                        }
                    } else {
                        // @HACK. Don't like having to check for imp specific
                        //        mailbox strings here but don't see anyway
                        //        around it.
                        // @TODO  REMOVE - deprecated in IMP 6.
                        if (strpos($mbox['ob']->utf8, "impsearch\000") === false &&
                            (!$subscriptions || ($mbox['a'] & 8))) {
                            $this->_mailboxlist[$mbox['ob']->utf8] = $mbox;
                        }
                    }
                }
            } catch (Horde_Exception $e) {
                Horde::log(sprintf(
                    'Error retrieving mailbox list: %s',
                    $e->getMessage()), 'ERR');
                throw new Horde_ActiveSync_Exception($e);
            }
        }
        try {
            $this->_mailboxlist = $injector->getInstance('Horde_Core_Hooks')->callHook(
                'activesync_mailboxlist',
                'horde',
                array($this->_mailboxlist)
            );
        } catch (Horde_Exception_HookNotSet $e) {}

        return $this->_mailboxlist;
    }

    /**
     * Return a list of the special mailboxes available on this server.
     *
     * @return array An array of special mailboxes.
     * @throws Horde_ActiveSync_Exception
     */
    public function getSpecialMailboxes()
    {
        global $injector, $registry, $prefs;

        if (empty($this->_specialMailboxlist)) {
            try {
                $this->_specialMailboxlist = $registry->mail->getSpecialMailboxes();
            } catch (Horde_Exception $e) {
                Horde::log(sprintf(
                    'Error retrieving specialmailbox list: %s',
                    $e->getMessage()), 'ERR');
                throw new Horde_ActiveSync_Exception($e);
            }

            // Sentmail is dependent on Identity.
            if (count($this->_specialMailboxlist['sent']) > 1) {
                $this->_specialMailboxlist['sent'] = $injector
                    ->getInstance('Horde_Core_Factory_Identity')
                    ->create($registry->getAuth(), $registry->hasInterface('mail'))
                    ->getValue('sent_mail_folder', $prefs->getValue('activesync_identity'));
            } else {
                $this->_specialMailboxlist['sent'] = current($this->_specialMailboxlist['sent']);
            }
        }

        return $this->_specialMailboxlist;
    }

    /**
     * Return a list of user-defined flags.
     *
     * @return array  An array of flag arrays keyed by the RFC 3501 flag name.
     */
    public function getMsgFlags()
    {
        global $registry;

        $msgFlags = array();
        $flags = unserialize($registry->horde->getPreference($registry->hasInterface('mail'), 'msgflags'));

        // Remove any system flags, as these should never be user (un)set.
        $system_flags = array(
            Horde_Imap_Client::FLAG_ANSWERED,
            Horde_Imap_Client::FLAG_DELETED,
            Horde_Imap_Client::FLAG_DRAFT,
            Horde_Imap_Client::FLAG_FLAGGED,
            Horde_Imap_Client::FLAG_RECENT,
            Horde_Imap_Client::FLAG_SEEN
        );
        foreach ($system_flags as $flag) {
            unset($flags[$flag]);
        }

        foreach ($flags as $flag) {
            if ($flag->imapflag) {
                   $msgFlags[Horde_String::lower($flag->imapflag)] = $flag->label;
            }
        }

        return $msgFlags;
    }

}
