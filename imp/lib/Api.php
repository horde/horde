<?php
/**
 * IMP external API interface.
 *
 * This file defines IMP's external API interface. Other applications
 * can interact with IMP through this API.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Api extends Horde_Registry_Api
{
    /**
     * The listing of API calls that do not require permissions checking.
     *
     * @var array
     */
    public $noPerms = array(
        'compose', 'batchCompose'
    );

    /**
     * Returns a compose window link.
     *
     * @param string|array $args  List of arguments to pass to compose.php.
     *                            If this is passed in as a string, it will be
     *                            parsed as a
     *                            toaddress?subject=foo&cc=ccaddress
     *                            (mailto-style) string.
     * @param array $extra        Hash of extra, non-standard arguments to
     *                            pass to compose.php.
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
     * @param string|array $args  List of arguments to pass to compose.php.
     *                            If this is passed in as a string, it will be
     *                            parsed as a
     *                            toaddress?subject=foo&cc=ccaddress
     *                            (mailto-style) string.
     * @param array $extra        List of hashes of extra, non-standard
     *                            arguments to pass to compose.php.
     *
     * @return array  The list of Horde_Url objects with links to the message
     *                composition screen.
     */
    public function batchCompose($args = array(), $extra = array())
    {
        $links = array();
        foreach ($args as $i => $arg) {
            $links[$i] = IMP::composeLink($arg, !empty($extra[$i]) ? $extra[$i] : array());
        }
        return $links;
    }

    /**
     * Returns the list of mailboxes.
     *
     * @return array  The list of IMAP mailboxes. A list of arrays with the
     *                following keys:
     *   - label: (string) Human readable label (UTF-8).
     *   - level: (integer) The child level of this element.
     *   - ob: (Horde_Imap_Client_Mailbox) A mailbox object.
     */
    public function mailboxList()
    {
        $mboxes = array();
        $imap_tree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');

        $imap_tree->setIteratorFilter(IMP_Imap_Tree::FLIST_NOCONTAINER);
        foreach ($imap_tree as $val) {
            $mboxes[] = array(
                'label' => $val->label,
                'level' => $val->level,
                'ob' => $val->imap_mbox_ob
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
        return $GLOBALS['injector']->getInstance('IMP_Message')->flag(
            $flags,
            new IMP_Indices($mailbox, $indices),
            $set
        );
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
        return isset($results[$mailbox])
            ? $results[$mailbox]
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
            'hostspec' => $imap_ob->ob->getParam('hostspec'),
            'port' => $imap_ob->ob->getParam('port'),
            'protocol' => $imap_ob->pop3 ? 'pop' : 'imap',
            'secure' => $imap_ob->ob->getParam('secure')
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
        return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->ob;
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
        if (!$GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FLAGS)) {
            return array();
        }

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

}
