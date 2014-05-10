<?php
/**
 * Horde_Core_ActiveSync_Imap_Factory
 *
 * PHP Version 5
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2014 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=Core
 * @package   Core
 */
/**
 * Horde_Core_ActiveSync_Imap_Factory:: Implements a factory/builder for
 * providing a Horde_ActiveSync_Imap_Adapter object as well as building
 * a tree of available mailboxes.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2014 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=Core
 * @package   Core
 */

class Horde_Core_ActiveSync_Imap_Factory implements Horde_ActiveSync_Interface_ImapFactory
{
    const MASK_SUBSCRIBED = 8;

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
        global $registry;

        if (empty($this->_mailboxlist) || $force) {
            $subscriptions = $registry->horde->getPreference(
                $registry->hasInterface('mail'),
                'subscribe'
            );
            try {
                foreach ($registry->mail->mailboxList() as $mbox) {
                    // @HACK. Don't like having to check for imp specific
                    //        mailbox strings here but don't see anyway around it.
                    if (strpos($mbox['ob']->utf8, "impsearch\000") === false &&
                        (!$subscriptions  || ($mbox['a'] & self::MASK_SUBSCRIBED))) {
                        $this->_mailboxlist[$mbox['ob']->utf8] = $mbox;
                    }
                }
            } catch (Horde_Exception $e) {
                Horde::log(sprintf(
                    'Error retrieving mailbox list: %s',
                    $e->getMessage()), 'ERR');
                throw new Horde_ActiveSync_Exception($e);
            }
        }

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

}