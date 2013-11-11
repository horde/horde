<?php
/**
 * Copyright 2009-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2009-2013 Horde LLC
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
 * @copyright 2009-2013 Horde LLC
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
     * @return array  The list of IMAP mailboxes. A list of arrays with the
     *                following keys:
     *   - d: (string) The namespace delimiter.
     *   - label: (string) Human readable label (UTF-8).
     *   - level: (integer) The child level of this element.
     *   - ob: (Horde_Imap_Client_Mailbox) A mailbox object.
     *   - subscribed: (boolean) True if mailbox is subscribed (@since 6.2.0).
     */
    public function mailboxList()
    {
        $iterator = new IMP_Ftree_IteratorFilter_Nocontainers(
            IMP_Ftree_IteratorFilter::create(
                IMP_Ftree_IteratorFilter::NO_REMOTE |
                IMP_Ftree_IteratorFilter::UNSUB_PREF
            )
        );
        $mboxes = array();

        foreach ($iterator as $val) {
            $mbox_ob = $val->mbox_ob;
            $sub = $mbox_ob->sub;

            $mboxes[] = array(
                // TODO: Remove for IMP 7.
                'a' => $sub ? 8 : 0,
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
        return $GLOBALS['injector']->getInstance('IMP_Message')->delete(
            new IMP_Indices($mailbox, $indices),
            array('nuke' => true)
        );
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
        return $GLOBALS['injector']->getInstance('IMP_Message')->copy(
            $target,
            'copy',
            new IMP_Indices($mailbox, $indices),
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
        return $GLOBALS['injector']->getInstance('IMP_Message')->copy(
            $target,
            'move',
            new IMP_Indices($mailbox, $indices),
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
        return $GLOBALS['injector']->getInstance('IMP_Message')->flag(array(
            ($set ? 'add' : 'remove') => $flags
        ), new IMP_Indices($mailbox, $indices));
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
     * @since 6.1.0
     *
     * @param string $mid  The Message-ID to obtain the log for.
     *
     * @return Horde_History_Log  The log object.
     */
    public function getMaillog($mid)
    {
        return ($log = $GLOBALS['injector']->getInstance('IMP_Maillog'))
            ? $log->getLog($mid)
            : new Horde_History_Log($mid);
    }

    /**
     * Log an entry in the Maillog.
     *
     * @since 6.1.0
     *
     * @param string $action  The action to log.
     * @param string $mid     The Message-ID.
     * @param string $data    Additional data.
     */
    public function logMaillog($action, $mid, $data = null)
    {
        if ($log = $GLOBALS['injector']->getInstance('IMP_Maillog')) {
            $log->log($action, $mid, $data);
        }
    }

    /**
     * Returns a list of Message-IDs that have been added to the Maillog since
     * the specified timestamp.
     *
     * @since 6.1.0
     *
     * @param integer $ts  The timestamp to start searching from. Only entries
     *                     after this timestamp will be returned.
     *
     * @return array  An array of Message-IDs that have been changed since $ts.
     */
    public function getMaillogChanges($ts)
    {
        return ($log = $GLOBALS['injector']->getInstance('IMP_Maillog'))
            ? preg_replace('/^([^:]*:){2}/', '', array_keys($log->getChanges($ts)))
            : array();
    }

}
