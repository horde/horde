<?php
/**
 * IMP external API interface.
 *
 * This file defines IMP's external API interface. Other applications
 * can interact with IMP through this API.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package IMP
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
     * @return string  The link to the message composition screen.
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
     * @return string  The list of links to the message composition screen.
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
     * Returns the list of folders.
     *
     * @return array  The list of IMAP folders.
     */
    public function folderlist()
    {
        $imp_folder = IMP_Folder::singleton();
        return $imp_folder->flist();
    }

    /**
     * Creates a new folder.
     *
     * @param string $folder  The name of the folder to create (UTF7-IMAP).
     *
     * @return string  The full folder name created or false on failure.
     * @throws Horde_Exception
     */
    public function createFolder($folder)
    {
        $imp_folder = IMP_Folder::singleton();
        $fname = $GLOBALS['imp_imap']->appendNamespace($folder);
        return $imp_folder->create($fname, $GLOBALS['prefs']->getValue('subscribe'))
            ? $fname
            : false;
    }

    /**
     * Deletes messages from a mailbox.
     *
     * @param string $mailbox  The name of the mailbox (UTF7-IMAP).
     * @param array $indices   The list of UIDs to delete.
     *
     * @return integer|boolean  The number of messages deleted if successful,
     *                          false if not.
     */
    public function deleteMessages($mailbox, $indices)
    {
        $imp_message = IMP_Message::singleton();
        return $imp_message->delete(array($mailbox => $indices), array('nuke' => true));
    }

    /**
     * Copies messages to a mailbox.
     *
     * @param string $mailbox  The name of the source mailbox (UTF7-IMAP).
     * @param array $indices   The list of UIDs to copy.
     * @param string $target   The name of the target mailbox (UTF7-IMAP).
     *
     * @return boolean  True if successful, false if not.
     */
    public function copyMessages($mailbox, $indices, $target)
    {
        $imp_message = IMP_Message::singleton();
        return $imp_message->copy($target, 'copy', array($mailbox => $indices), true);
    }

    /**
     * Moves messages to a mailbox.
     *
     * @param string $mailbox  The name of the source mailbox (UTF7-IMAP).
     * @param array $indices   The list of UIDs to move.
     * @param string $target   The name of the target mailbox (UTF7-IMAP).
     *
     * @return boolean  True if successful, false if not.
     */
    public function moveMessages($mailbox, $indices, $target)
    {
        $imp_message = IMP_Message::singleton();
        return $imp_message->copy($target, 'move', array($mailbox => $indices), true);
    }

    /**
     * Flag messages.
     *
     * @param string $mailbox  The name of the source mailbox (UTF7-IMAP).
     * @param array $indices   The list of UIDs to flag.
     * @param array $flags     The flags to set.
     * @param boolean $set     True to set flags, false to clear flags.
     *
     * @return boolean  True if successful, false if not.
     */
    public function flagMessages($mailbox, $indices, $flags, $set)
    {
        $imp_message = IMP_Message::singleton();
        return $imp_message->flag($flags, array($mailbox => $indices), $set);
    }

    /**
     * Return envelope information for the given list of indices.
     *
     * @param string $mailbox  The name of the mailbox (UTF7-IMAP).
     * @param array $indices   The list of UIDs.
     *
     * @return array  The envelope information. See
     *                Horde_Imap_Client_Base::fetch() for the format.
     * @throws Horde_Imap_Client_Exception
     */
    public function msgEnvelope($mailbox, $indices)
    {
        return $GLOBALS['imp_imap']->ob()->fetch($mailbox, array(Horde_Imap_Client::FETCH_ENVELOPE => true), array('ids' => $indices));
    }

    /**
     * Perform a search query on the remote IMAP server.
     *
     * @param string $mailbox                        The name of the source
     *                                               mailbox (UTF7-IMAP).
     * @param Horde_Imap_Client_Search_Query $query  The query object.
     *
     * @return array  The search results (UID list).
     */
    public function searchMailbox($mailbox, $query)
    {
        return $GLOBALS['imp_search']->runSearchQuery($query, $mailbox);
    }

    /**
     * Returns the cache ID value for a mailbox
     *
     * @param string $mailbox  The name of the source mailbox (UTF7-IMAP).
     *
     * @return string  The cache ID string.
     * @throws Horde_Imap_Client_Exception
     */
    public function mailboxCacheId($mailbox)
    {
        return $GLOBALS['imp_imap']->ob()->getCacheId($mailbox);
    }

    /**
     * Returns information on the currently logged on IMAP server.
     *
     * @return mixed  An array with the following entries:
     * <pre>
     * 'hostspec' - (string) The server hostname.
     * 'port' - (integer) The server port.
     * 'protocol' - (string) Either 'imap' or 'pop'.
     * 'secure' - (string) Either 'none', 'ssl', or 'tls'.
     * </pre>
     */
    public function server()
    {
        $imap_obj = unserialize($_SESSION['imp']['imap_ob'][$_SESSION['imp']['server_key']]);
        return array(
            'hostspec' => $imap_obj->getParam('hostspec'),
            'port' => $imap_obj->getParam('port'),
            'protocol' => $_SESSION['imp']['protocol'],
            'secure' => $imap_obj->getParam('secure')
        );
    }

    /**
     * Returns the list of favorite recipients.
     *
     * @param integer $limit  Return this number of recipients.
     * @param array $filter   A list of messages types that should be returned.
     *                        A value of null returns all message types.
     *
     * @return array  A list with the $limit most favourite recipients.
     */
    public function favouriteRecipients($limit,
                                        $filter = array('new', 'forward', 'reply', 'redirect'))
    {
        $sentmail = IMP_Sentmail::factory();
        return $sentmail->favouriteRecipients($limit, $filter);
    }

}
