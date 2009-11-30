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
        require_once dirname(__FILE__) . '/Application.php';
        new IMP_Application(array('init' => array('authentication' => 'none')));

        $links = array();
        foreach ($args as $i => $arg) {
            $links[$i] = IMP::composeLink($arg, !empty($extra[$i]) ? $extra[$i] : array());
        }

        return $links;
    }

    /**
     * Returns the list of folders.
     *
     * @return array  The list of IMAP folders or false if not available.
     */
    public function folderlist()
    {
        require_once dirname(__FILE__) . '/Application.php';
        try {
            new IMP_Application(array('init' => array('authentication' => 'throw')));
        } catch (Horde_Exception $e) {
            return false;
        }

        $imp_folder = IMP_Folder::singleton();
        return $imp_folder->flist();
    }

    /**
     * Creates a new folder.
     *
     * @param string $folder  The name of the folder to create (UTF7-IMAP).
     *
     * @return string  The full folder name created or false on failure.
     */
    public function createFolder($folder)
    {
        require_once dirname(__FILE__) . '/Application.php';
        try {
            new IMP_Application(array('init' => array('authentication' => 'throw')));
        } catch (Horde_Exception $e) {
            return false;
        }

        $imp_folder = IMP_Folder::singleton();
        return $imp_folder->create($GLOBALS['imp_imap']->appendNamespace($folder), $GLOBALS['prefs']->getValue('subscribe'));
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
        require_once dirname(__FILE__) . '/Application.php';
        try {
            new IMP_Application(array('init' => array('authentication' => 'throw')));
        } catch (Horde_Exception $e) {
            return false;
        }

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
        require_once dirname(__FILE__) . '/Application.php';
        try {
            new IMP_Application(array('init' => array('authentication' => 'throw')));
        } catch (Horde_Exception $e) {
            return false;
        }

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
        require_once dirname(__FILE__) . '/Application.php';
        try {
            new IMP_Application(array('init' => array('authentication' => 'throw')));
        } catch (Horde_Exception $e) {
            return false;
        }

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
        require_once dirname(__FILE__) . '/Application.php';
        try {
            new IMP_Application(array('init' => array('authentication' => 'throw')));
        } catch (Horde_Exception $e) {
            return false;
        }

        $imp_message = IMP_Message::singleton();
        return $imp_message->flag($flags, 'move', array($mailbox => $indices), $set);
    }

    /**
     * Return envelope information for the given list of indices.
     *
     * @param string $mailbox  The name of the mailbox (UTF7-IMAP).
     * @param array $indices   The list of UIDs.
     *
     * @return array|boolean  TODO if successful, false if not.
     */
    public function msgEnvelope($mailbox, $indices)
    {
        require_once dirname(__FILE__) . '/Application.php';
        try {
            new IMP_Application(array('init' => array('authentication' => 'throw')));
        } catch (Horde_Exception $e) {
            return false;
        }

        return $GLOBALS['imp_imap']->ob()->fetch($mailbox, array(Horde_Imap_Client::FETCH_ENVELOPE => true), array('ids' => $indices));
    }

    /**
     * Perform a search query on the remote IMAP server.
     *
     * @param string $mailbox                        The name of the source
     *                                               mailbox (UTF7-IMAP).
     * @param Horde_Imap_Client_Search_Query $query  The query object.
     *
     * @return array|boolean  The search results (UID list) or false.
     */
    public function searchMailbox($mailbox, $query)
    {
        require_once dirname(__FILE__) . '/Application.php';
        try {
            new IMP_Application(array('init' => array('authentication' => 'throw')));
        } catch (Horde_Exception $e) {
            return false;
        }

        return $GLOBALS['imp_search']->runSearchQuery($query, $mailbox);
    }

    /**
     * Returns the cache ID value for a mailbox
     *
     * @param string $mailbox  The name of the source mailbox (UTF7-IMAP).
     *
     * @return string|boolean  The cache ID value, or false if not
     *                         authenticated.
     */
    public function mailboxCacheId($mailbox)
    {
        require_once dirname(__FILE__) . '/Application.php';
        try {
            new IMP_Application(array('init' => array('authentication' => 'throw')));
        } catch (Horde_Exception $e) {
            return false;
        }

        return $GLOBALS['imp_imap']->ob()->getCacheId($mailbox);
    }

    /**
     * Returns information on the currently logged on IMAP server.
     *
     * @return mixed  Returns null if the user has not authenticated into IMP
     *                yet Otherwise, an array with the following entries:
     * <pre>
     * 'hostspec' - (string) The server hostname.
     * 'port' - (integer) The server port.
     * 'protocol' - (string) Either 'imap' or 'pop'.
     * 'secure' - (string) Either 'none', 'ssl', or 'tls'.
     * </pre>
     */
    public function server()
    {
        require_once dirname(__FILE__) . '/Application.php';
        try {
            new IMP_Application(array('init' => array('authentication' => 'throw')));
        } catch (Horde_Exception $e) {
            return null;
        }

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
        require_once dirname(__FILE__) . '/Application.php';
        new IMP_Application(array('init' => array('authentication' => 'none')));

        if ($GLOBALS['conf']['sentmail']['driver'] != 'none') {
            $sentmail = IMP_Sentmail::factory();
            return $sentmail->favouriteRecipients($limit, $filter);
        }

        return array();
    }

}
