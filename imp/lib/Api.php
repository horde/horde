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
 * IMP external API interface.
 *
 * This file defines IMP's external API interface. Other applications
 * can interact with IMP through this API.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Api extends Horde_Registry_Api
{
    /**
     * The listing of API calls that do not require permissions checking.
     *
     * @var array
     */
    protected $_noPerms = array(
        'compose', 'batchCompose'
    );

    /**
     * Returns a compose window link.
     *
     * @param string|array $args  List of arguments to pass to compose page.
     *                            If this is passed in as a string, it will be
     *                            parsed as a
     *                            toaddress?subject=foo&cc=ccaddress
     *                            (mailto-style) string.
     * @param array $extra        Hash of extra, non-standard arguments to
     *                            pass to compose page.
     *
     * @return Horde_Url  The link to the message composition screen.
     */
    public function compose($args = array(), $extra = array())
    {
        $link = $this->batchCompose(array($args), array($extra));
        return $link[0];
    }

    /**
     * Return a list of compose window links.
     *
     * @param string|array $args  List of arguments to pass to compose page.
     *                            If this is passed in as a string, it will be
     *                            parsed as a
     *                            toaddress?subject=foo&cc=ccaddress
     *                            (mailto-style) string.
     * @param array $extra        List of hashes of extra, non-standard
     *                            arguments to pass to compose page.
     *
     * @return array  The list of Horde_Url objects with links to the message
     *                composition screen.
     */
    public function batchCompose($args = array(), $extra = array())
    {
        $links = array();
        foreach ($args as $i => $arg) {
            $tmp = new IMP_Compose_Link($arg);
            $links[$i] = $tmp->link();
            if (!empty($extra[$i])) {
                $links[$i]->add($extra[$i]);
            }
        }
        return $links;
    }

    /**
     * Returns the list of mailboxes.
     *
     * @param array $opts  Additional options:
     * <pre>
     *   - unsub: (boolean) If true, return unsubscribed mailboxes.
     * </pre>
     *
     * @return array  The list of IMAP mailboxes. A list of arrays with the
     *                following keys:
     * <pre>
     *   - d: (string) The namespace delimiter.
     *   - label: (string) Human readable label (UTF-8).
     *   - level: (integer) The child level of this element.
     *   - ob: (Horde_Imap_Client_Mailbox) A mailbox object.
     *   - subscribed: (boolean) True if mailbox is subscribed.
     * </pre>
     */
    public function mailboxList(array $opts = array())
    {
        global $injector;

        $ftree = $injector->getInstance('IMP_Ftree');

        if (!empty($opts['unsub'])) {
            /* Make sure unsubscribed mailboxes are loaded. */
            $ftree->loadUnsubscribed();
        }

        $iterator = new IMP_Ftree_IteratorFilter($ftree);
        $iterator->add(array(
            $iterator::CONTAINERS,
            $iterator::REMOTE,
            $iterator::VFOLDER
        ));
        if (!empty($opts['unsub'])) {
            $iterator->remove($iterator::UNSUB);
        }
        $mboxes = array();

        foreach ($iterator as $val) {
            $mbox_ob = $val->mbox_ob;
            $sub = $mbox_ob->sub;

            $mboxes[] = array(
                'd' => $mbox_ob->namespace_delimiter,
                'label' => $mbox_ob->label,
                'level' => $val->level,
                'ob' => $mbox_ob->imap_mbox_ob,
                'subscribed' => $sub
            );
        }

        return $mboxes;
    }

    /**
     * Creates a new mailbox.
     *
     * @param string $mbox    The name of the mailbox to create (UTF-8).
     * @param array $options  Additional options:
     *   - full: (boolean) If true, $mbox is a full mailbox name. If false,
     *           $mbox will be created in the default namespace.
     *           DEFAULT: false
     *
     * @return Horde_Imap_Client_Mailbox  The mailbox name created or false on
     *                                    failure.
     *
     * @throws IMP_Exception
     */
    public function createMailbox($mbox, array $options = array())
    {
        $fname = IMP_Mailbox::get($mbox);
        if (empty($options['full'])) {
            $fname = $fname->namespace_append;
        }

        return $fname->create()
            ? $fname->imap_mbox_ob
            : false;
    }

    /**
     * Deletes messages from a mailbox.
     *
     * @param string $mailbox  The name of the mailbox (UTF-8).
     * @param array $indices   The list of UIDs to delete.
     *
     * @return integer|boolean  The number of messages deleted if successful,
     *                          false if not.
     */
    public function deleteMessages($mailbox, $indices)
    {
        $i = new IMP_Indices($mailbox, $indices);
        return $i->delete(array('nuke' => true));
    }

    /**
     * Copies messages to a mailbox.
     *
     * @param string $mailbox  The name of the source mailbox (UTF-8).
     * @param array $indices   The list of UIDs to copy.
     * @param string $target   The name of the target mailbox (UTF-8).
     *
     * @return boolean  True if successful, false if not.
     */
    public function copyMessages($mailbox, $indices, $target)
    {
        $i = new IMP_Indices($mailbox, $indices);

        return $i->copy(
            $target,
            'copy',
            array('create' => true)
        );
    }

    /**
     * Moves messages to a mailbox.
     *
     * @param string $mailbox  The name of the source mailbox (UTF-8).
     * @param array $indices   The list of UIDs to move.
     * @param string $target   The name of the target mailbox (UTF-8).
     *
     * @return boolean  True if successful, false if not.
     */
    public function moveMessages($mailbox, $indices, $target)
    {
        $i = new IMP_Indices($mailbox, $indices);

        return $i->copy(
            $target,
            'move',
            array('create' => true)
        );
    }

    /**
     * Flag messages.
     *
     * @param string $mailbox  The name of the source mailbox (UTF-8).
     * @param array $indices   The list of UIDs to flag.
     * @param array $flags     The flags to set.
     * @param boolean $set     True to set flags, false to clear flags.
     *
     * @return boolean  True if successful, false if not.
     */
    public function flagMessages($mailbox, $indices, $flags, $set)
    {
        $i = new IMP_Indices($mailbox, $indices);
        return $i->flag(
            $set ? $flags : array(),
            $set ? array() : $flags
        );
    }

    /**
     * Ensures a list of user-defined IMAP flag(s) for the current user exist.
     * Silently ignores any flags that are already defined.
     *
     * @param array $flags  An array of user-defined flag names.
     */
    public function addFlags(array $flags)
    {
        $imp_flags = $GLOBALS['injector']->getInstance('IMP_Flags');
        foreach ($flags as $flag) {
            try {
                $imp_flags->addFlag($flag);
            } catch (IMP_Exception $e) {
            }
        }
    }

    /**
     * Perform a search query on the remote IMAP server.
     *
     * @param string $mailbox                        The name of the source
     *                                               mailbox (UTF-8).
     * @param Horde_Imap_Client_Search_Query $query  The query object.
     *
     * @return array  The search results (UID list).
     */
    public function searchMailbox($mailbox, $query)
    {
        $results = IMP_Mailbox::get($mailbox)->runSearchQuery($query);
        return isset($results[strval($mailbox)])
            ? $results[strval($mailbox)]
            : array();
    }

    /**
     * Returns information on the currently logged on IMAP server.
     *
     * @return mixed  An array with the following entries:
     *   - hostspec: (string) The server hostname.
     *   - port: (integer) The server port.
     *   - protocol: (string) Either 'imap' or 'pop'.
     *   - secure: (string) Either 'none', 'ssl', or 'tls'.
     */
    public function server()
    {
        $imap_ob = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

        return array(
            'hostspec' => $imap_ob->getParam('hostspec'),
            'port' => $imap_ob->getParam('port'),
            'protocol' => $imap_ob->isImap() ? 'imap' : 'pop',
            'secure' => $imap_ob->getParam('secure')
        );
    }

    /**
     * Returns the list of favorite recipients.
     *
     * @param integer $limit  Return this number of recipients.
     * @param array $filter   A list of messages types that should be
     *                        returned.  Valid types: 'forward', 'mdn', 'new',
     *                        'reply', and 'redirect'. A value of null returns
     *                        all message types.
     *
     * @return array  A list with the $limit most favourite recipients.
     * @throws IMP_Exception
     */
    public function favouriteRecipients($limit,
                                        $filter = array('new', 'forward', 'reply', 'redirect'))
    {
        if (!empty($filter)) {
            $new_filter = array();
            foreach ($filter as $val) {
                switch ($val) {
                case 'forward':
                    $new_filter[] = IMP_Sentmail::FORWARD;
                    break;

                case 'mdn':
                    $new_filter[] = IMP_Sentmail::MDN;
                    break;

                case 'new':
                    $new_filter[] = IMP_Sentmail::NEWMSG;
                    break;

                case 'redirect':
                    $new_filter[] = IMP_Sentmail::REDIRECT;
                    break;

                case 'reply':
                    $new_filter[] = IMP_Sentmail::REPLY;
                    break;
                }
            }

            $filter = $new_filter;
        }

        return $GLOBALS['injector']->getInstance('IMP_Sentmail')->favouriteRecipients($limit, $filter);
    }

    /**
     * Log an entry to the IMP_Sentmail system.
     *
     * @param string $action            The performed action. One of:
     *   - forward
     *   - mdn
     *   - new
     *   - redirect
     *   - reply
     * @param string|array $recipients  The message recipients.
     * @param string $message_id        The Message-ID.
     * @param boolean $success          Was the message successfully sent?
     */
    public function logRecipient(
        $reason, $recipients, $message_id, $success = true
    )
    {
        $GLOBALS['injector']->getInstance('IMP_Sentmail')->log(
            $reason,
            $message_id,
            $recipients,
            $success
        );
    }

    /**
     * Returns the Horde_Imap_Client object created using the IMP credentials.
     *
     * @return Horde_Imap_Client_Base  The imap object.
     */
    public function imapOb()
    {
        return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->client_ob;
    }

    /**
     * Return the list of user-settable IMAP flags.
     *
     * @param string $mailbox  If set, returns the list of flags filtered by
     *                         what the mailbox allows (UTF-8).
     *
     * @return array  A list of IMP_Flag_Base objects.
     */
    public function flagList($mailbox = null)
    {
        return $GLOBALS['injector']->getInstance('IMP_Flags')->getList(array(
            'imap' => true,
            'mailbox' => $mailbox
        ));
    }

    /**
     * Return the list of special mailboxes.
     *
     * @return @see IMP_Mailbox::getSpecialMailboxes()
     */
    public function getSpecialMailboxes()
    {
        return IMP_Mailbox::getSpecialMailboxes();
    }

    /**
     * Obtain the Maillog for a given message.
     *
     * @todo  This should not be returning a Horde_History_Log object.
     *
     * @param string $mid  The Message-ID to obtain the log for.
     *
     * @return Horde_History_Log  The log object.
     */
    public function getMaillog($mid)
    {
        global $injector, $registry;

        $log = $injector->getInstance('IMP_Maillog')->getLog(
            new IMP_Maillog_Message($mid)
        );

        $history = array();
        foreach ($log as $val) {
            $history[] = array(
                'history_action' => $val->action,
                'history_desc' => '',
                'history_id' => 0,
                'history_modseq' => 0,
                'history_ts' => $val->timestamp,
                'history_who' => $registry->getAuth()
            );
        }

        return new Horde_History_Log($mid, $history);
    }

    /**
     * Log an entry in the Maillog.
     *
     * @todo  Rewrite this. $action and $data are both IMP specific, so they
     *        aren't intended to be set from outside of IMP. And $mid should
     *        be replaced by mailbox/UID.
     *
     * @param string $action  The action to log.
     * @param string $mid     The Message-ID.
     * @param string $data    Additional data.
     */
    public function logMaillog($action, $mid, $data = null)
    {
        switch ($action) {
        case 'forward':
            $log = new IMP_Maillog_Log_Forward(array(
                'recipients' => $data['recipients']
            ));
            break;

        case 'mdn':
            $log = new IMP_Maillog_Log_Mdn();
            break;

        case 'redirect':
            $log = new IMP_Maillog_Log_Redirect(array(
                'recipients' => $data['recipients']
            ));
            break;

        case 'reply':
            $log = new IMP_Maillog_Log_Reply();
            break;

        case 'reply_all':
            $log = new IMP_Maillog_Log_Replyall();
            break;

        case 'reply_list':
            $log = new IMP_Maillog_Log_Replylist();
            break;
        }

        $GLOBALS['injector']->getInstance('IMP_Maillog')->log(
            new IMP_Maillog_Message($mid),
            $log
        );
    }

    /**
     * Returns a list of Message-IDs that have been added to the Maillog since
     * the specified timestamp.
     *
     * @todo  This should not be returning Message-IDs.
     *
     * @param integer $ts  The timestamp to start searching from. Only entries
     *                     after this timestamp will be returned.
     *
     * @return array  An array of Message-IDs that have been changed since $ts.
     */
    public function getMaillogChanges($ts)
    {
        return array_map(
            'strval',
            $GLOBALS['injector']->getInstance('IMP_Maillog')->getChanges($ts)
        );
    }

}
