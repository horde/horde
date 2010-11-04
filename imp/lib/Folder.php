<?php
/**
 * The IMP_Folder:: class provides a set of methods for dealing with folders,
 * accounting for subscription, errors, etc.
 *
 * @todo Don't use notification.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Folder
{
    /**
     * Mapping of special-use keys to their IMP equivalents.
     *
     * @var array
     */
    static public $specialUse = array(
        'drafts' => '\\drafts',
        'sent' => '\\sent',
        'spam' => '\\junk',
        'trash' => '\\trash'
    );

    /**
     * Deletes one or more folders.
     *
     * @param array $folders  Folders to be deleted (UTF7-IMAP).
     * @param boolean $force  Delete folders even if fixed?
     *
     * @return boolean  Were folders successfully deleted?
     */
    public function delete($folders, $force = false)
    {
        global $conf, $notification;

        $return_value = true;
        $deleted = array();

        foreach ($folders as $folder) {
            if (!$force &&
                !empty($conf['server']['fixed_folders']) &&
                in_array(IMP::folderPref($folder, false), $conf['server']['fixed_folders'])) {
                $notification->push(sprintf(_("The folder \"%s\" may not be deleted."), IMP::displayFolder($folder)), 'horde.error');
                continue;
            }

            try {
                $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->deleteMailbox($folder);
                $notification->push(sprintf(_("The folder \"%s\" was successfully deleted."), IMP::displayFolder($folder)), 'horde.success');
                $deleted[] = $folder;
            } catch (Horde_Imap_Client_Exception $e) {
                $notification->push(sprintf(_("The folder \"%s\" was not deleted. This is what the server said"), IMP::displayFolder($folder)) . ': ' . $e->getMessage(), 'horde.error');
            }
        }

        if (!empty($deleted)) {
            $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->delete($deleted);
            $this->_onDelete($deleted);
        }

        return $return_value;
    }

    /**
     * Do the necessary cleanup/cache updates when deleting folders.
     *
     * @param array $deleted  The list of deleted folders.
     */
    protected function _onDelete($deleted)
    {
        /* Clear the folder from the sort prefs. */
        foreach ($deleted as $val) {
            IMP::setSort(null, null, $val, true);
        }
    }

    /**
     * Create a new IMAP folder if it does not already exist, and subcribe to
     * it as well if requested.
     *
     * @param string $folder      The folder to be created (UTF7-IMAP).
     * @param boolean $subscribe  Subscribe to folder?
     * @param array $opts         Additional options:
     * <pre>
     * 'drafts' - (boolean) Is this a drafts mailbox?
     *            DEFAULT: false
     * 'spam' - (boolean) Is this a spam mailbox?
     *          DEFAULT: false
     * 'sent' - (boolean) Is this a sent-mail mailbox?
     *          DEFAULT: false
     * 'trash' - (boolean) Is this a trash mailbox?
     *          DEFAULT: false
     * </pre>
     *
     * @return boolean  Whether or not the folder was successfully created.
     * @throws Horde_Exception
     */
    public function create($folder, $subscribe)
    {
        global $conf, $injector, $notification;

        /* Check permissions. */
        $perms = $injector->getInstance('Horde_Perms');
        if (!$perms->hasAppPermission('create_folders')) {
            Horde::permissionDeniedError(
                'imp',
                'create_folders',
                _("You are not allowed to create folders.")
            );
            return false;
        } elseif (!$perms->hasAppPermission('max_folders')) {
            Horde::permissionDeniedError(
                'imp',
                'max_folders',
                sprintf(_("You are not allowed to create more than %d folders."), $perms->hasAppPermission('max_folders', array('opts' => array('value' => true))))
            );
            return false;
        }

        /* Make sure we are not trying to create a duplicate folder */
        if ($this->exists($folder)) {
            $notification->push(sprintf(_("The folder \"%s\" already exists"), IMP::displayFolder($folder)), 'horde.warning');
            return false;
        }

        /* Special use flags. */
        $special_use = array();
        foreach (self::$specialUse as $key => $val) {
            if (!empty($this->_opts[$key])) {
                $special_use[] = $val;
            }
        }

        /* Attempt to create the mailbox. */
        try {
            $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->createMailbox($folder, array('special_use' => $special_use));
        } catch (Horde_Imap_Client_Exception $e) {
            $notification->push(sprintf(_("The folder \"%s\" was not created. This is what the server said"), IMP::displayFolder($folder)) . ': ' . $e->getMessage(), 'horde.error');
            return false;
        }

        $GLOBALS['notification']->push(sprintf(_("The folder \"%s\" was successfully created."), IMP::displayFolder($folder)), 'horde.success');

        /* Subscribe, if requested. */
        if ($subscribe) {
            $this->subscribe(array($folder));
        }

        /* Update the mailbox tree. */
        $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->insert($folder);

        return true;
    }

    /**
     * Finds out if a specific folder exists or not.
     *
     * @param string $folder  The folder name to be checked (UTF7-IMAP).
     *
     * @return boolean  Does the folder exist?
     */
    public function exists($folder)
    {
        $imaptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        if (isset($imaptree[$folder])) {
            return !$imaptree[$folder]->container;
        }

        try {
            $ret = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->listMailboxes($folder, array('flat' => true));
            return !empty($ret);
        } catch (Horde_Imap_Client_Exception $e) {
            return false;
        }
    }

    /**
     * Renames an IMAP folder. The subscription status remains the same.  All
     * subfolders will also be renamed.
     *
     * @param string $old     The old folder name (UTF7-IMAP).
     * @param string $new     The new folder name (UTF7-IMAP).
     * @param boolean $force  Rename folders even if they are fixed?
     *
     * @return boolean  Were all folder(s) successfully renamed?
     */
    public function rename($old, $new, $force = false)
    {
        /* Don't try to rename from or to an empty string. */
        if ((strlen($old) == 0) || (strlen($new) == 0)) {
            return false;
        }

        if (!$force &&
            !empty($GLOBALS['conf']['server']['fixed_folders']) &&
            in_array(IMP::folderPref($old, false), $GLOBALS['conf']['server']['fixed_folders'])) {
            $GLOBALS['notification']->push(sprintf(_("The folder \"%s\" may not be renamed."), IMP::displayFolder($old)), 'horde.error');
            return false;
        }

        $deleted = array($old);
        $inserted = array($new);

        $imaptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imaptree->setIteratorFilter(IMP_Imap_Tree::FLIST_NOCONTAINER | IMP_Imap_Tree::FLIST_UNSUB | IMP_Imap_Tree::FLIST_NOBASE, $old);

        /* Get list of any folders that are underneath this one. */
        $all_folders = array_merge(array($old), array_keys(iterator_to_array($imaptree)));

        try {
            $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->renameMailbox($old, $new);
        } catch (Horde_Imap_Client_Exception $e) {
            $GLOBALS['notification']->push(sprintf(_("Renaming \"%s\" to \"%s\" failed. This is what the server said"), IMP::displayFolder($old), IMP::displayFolder($new)) . ': ' . $e->getMessage(), 'horde.error');
            return false;
        }

        $GLOBALS['notification']->push(sprintf(_("The folder \"%s\" was successfully renamed to \"%s\"."), IMP::displayFolder($old), IMP::displayFolder($new)), 'horde.success');

        foreach ($all_folders as $folder_old) {
            $deleted[] = $folder_old;

            /* Get the new folder name. */
            $inserted[] = $folder_new = substr_replace($folder_old, $new, 0, strlen($old));
        }

        if (!empty($deleted)) {
            $imaptree->rename($deleted, $inserted);
            $this->_onDelete($deleted);
        }

        return true;
    }

    /**
     * Subscribes to one or more IMAP folders.
     *
     * @param array $folders  The folders to subscribe to (UTF7-IMAP).
     *
     * @return boolean  Were all folders successfully subscribed to?
     */
    public function subscribe($folders)
    {
        global $notification;

        $return_value = true;
        $subscribed = array();

        if (!is_array($folders)) {
            $notification->push(_("No folders were specified"), 'horde.warning');
            return false;
        }

        foreach (array_filter($folders) as $folder) {
            try {
                $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->subscribeMailbox($folder, true);
                $notification->push(sprintf(_("You were successfully subscribed to \"%s\""), IMP::displayFolder($folder)), 'horde.success');
                $subscribed[] = $folder;
            } catch (Horde_Imap_Client_Exception $e) {
                $notification->push(sprintf(_("You were not subscribed to \"%s\". Here is what the server said"), IMP::displayFolder($folder)) . ': ' . $e->getMessage(), 'horde.error');
                $return_value = false;
            }
        }

        if (!empty($subscribed)) {
            $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->subscribe($subscribed);
        }

        return $return_value;
    }

    /**
     * Unsubscribes from one or more IMAP folders.
     *
     * @param array $folders  The folders to unsubscribe from (UTF7-IMAP).
     *
     * @return boolean  Were all folders successfully unsubscribed from?
     */
    public function unsubscribe($folders)
    {
        global $notification;

        $return_value = true;
        $unsubscribed = array();

        if (!is_array($folders)) {
            $notification->push(_("No folders were specified"), 'horde.message');
            return false;
        }

        foreach (array_filter($folders) as $folder) {
            if (strcasecmp($folder, 'INBOX') == 0) {
                $notification->push(sprintf(_("You cannot unsubscribe from \"%s\"."), IMP::displayFolder($folder)), 'horde.error');
            } else {
                try {
                    $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->subscribeMailbox($folder, false);
                    $notification->push(sprintf(_("You were successfully unsubscribed from \"%s\""), IMP::displayFolder($folder)), 'horde.success');
                    $unsubscribed[] = $folder;
                } catch (Horde_Imap_Client_Exception $e) {
                    $notification->push(sprintf(_("You were not unsubscribed from \"%s\". Here is what the server said"), IMP::displayFolder($folder)) . ': ' . $e->getMessage(), 'horde.error');
                    $return_value = false;
                }
            }
        }

        if (!empty($unsubscribed)) {
            $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->unsubscribe($unsubscribed);
        }

        return $return_value;
    }

    /**
     * Generates a string that can be saved out to an mbox format mailbox file
     * for a folder or set of folders, optionally including all subfolders of
     * the selected folders as well. All folders will be put into the same
     * string.
     *
     * @author Didi Rieder <adrieder@sbox.tugraz.at>
     *
     * @param array $folder_list  A list of folder names to generate a mbox
     *                            file for (UTF7-IMAP).
     *
     * @return resource  A stream resource containing the text of a mbox
     *                   format mailbox file.
     */
    public function generateMbox($folder_list)
    {
        $body = fopen('php://temp', 'r+');

        if (empty($folder_list)) {
            return $body;
        }

        foreach ($folder_list as $folder) {
            try {
                $status = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->status($folder, Horde_Imap_Client::STATUS_MESSAGES);
            } catch (Horde_Imap_Client_Exception $e) {
                continue;
            }
            for ($i = 1; $i <= $status['messages']; ++$i) {
                /* Download one message at a time to save on memory
                 * overhead. */
                try {
                    $res = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->fetch($folder, array(
                        Horde_Imap_Client::FETCH_FULLMSG => array('peek' => true, 'stream' => true),
                        Horde_Imap_Client::FETCH_ENVELOPE => true,
                        Horde_Imap_Client::FETCH_DATE => true,
                    ), array('ids' => array($i), 'sequence' => true));
                    $ptr = reset($res);
                } catch (Horde_Imap_Client_Exception $e) {
                    continue;
                }

                $from = '<>';
                if (!empty($ptr['envelope']['from'])) {
                    $ptr2 = reset($ptr['envelope']['from']);
                    if (!empty($ptr2['mailbox']) && !empty($ptr2['host'])) {
                        $from = $ptr2['mailbox']. '@' . $ptr2['host'];
                    }
                }

                /* We need this long command since some MUAs (e.g. pine)
                 * require a space in front of single digit days. */
                $date = sprintf('%s %2s %s', $ptr['date']->format('D M'), $ptr['date']->format('j'), $ptr['date']->format('H:i:s Y'));
                fwrite($body, 'From ' . $from . ' ' . $date . "\r\n");
                rewind($ptr['fullmsg']);
                stream_copy_to_stream($ptr['fullmsg'], $body);
                fclose($ptr['fullmsg']);
                fwrite($body, "\r\n");
            }
        }

        return $body;
    }

    /**
     * Imports messages into a given folder from a mbox format mailbox file.
     *
     * @param string $folder  The folder to put the messages into (UTF7-IMAP).
     * @param string $mbox    String containing the mbox filename.
     *
     * @return mixed  False (boolean) on fail or the number of messages
     *                imported (integer) on success.
     */
    public function importMbox($folder, $mbox)
    {
        $message = '';
        $msgcount = 0;
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create();

        $fd = fopen($mbox, 'r');
        while (!feof($fd)) {
            $line = fgets($fd);

            if (preg_match('/From (.+@.+|- )/A', $line)) {
                if (!empty($message)) {
                    try {
                        $imp_imap->append($folder, array(array('data' => $message)));
                        ++$msgcount;
                    } catch (Horde_Imap_Client_Exception $e) {}
                }
                $message = '';
            } else {
                $message .= $line;
            }
        }
        fclose($fd);

        if (!empty($message)) {
            try {
                $imp_imap->append($folder, array(array('data' => $message)));
                ++$msgcount;
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        return $msgcount ? $msgcount : false;
    }

}
