<?php
/**
 * The IMP_Folder:: class provides a set of methods for dealing with folders,
 * accounting for subscription, errors, etc.
 *
 * @todo Don't use notification.
 *
 * Copyright 2000-2011 The Horde Project (http://www.horde.org/)
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

        foreach (IMP_Mailbox::get($folders) as $folder) {
            if (!$force && $folder->fixed) {
                $notification->push(sprintf(_("The folder \"%s\" may not be deleted."), $folder->display), 'horde.error');
                continue;
            }

            try {
                $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->deleteMailbox($folder);
                $notification->push(sprintf(_("The folder \"%s\" was successfully deleted."), $folder->display), 'horde.success');
                $deleted[] = $folder;
            } catch (Horde_Imap_Client_Exception $e) {
                $notification->push(sprintf(_("The folder \"%s\" was not deleted. This is what the server said"), $folder->display) . ': ' . $e->getMessage(), 'horde.error');
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
        foreach (IMP_Mailbox::get($deleted) as $val) {
            $val->setSort(null, null, true);
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
    public function create($folder, $subscribe, array $opts = array())
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
                sprintf(_("You are not allowed to create more than %d folders."), $perms->getPermissions('max_folders'))
            );
            return false;
        }

        $folder = IMP_Mailbox::get($folder);

        /* Make sure we are not trying to create a duplicate folder */
        if ($folder->exists) {
            $notification->push(sprintf(_("The folder \"%s\" already exists"), $folder->display), 'horde.warning');
            return false;
        }

        /* Special use flags. */
        $special_use = array();
        foreach (self::$specialUse as $key => $val) {
            if (!empty($opts[$key])) {
                $special_use[] = $val;
            }
        }

        /* Attempt to create the mailbox. */
        try {
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->createMailbox($folder, array('special_use' => $special_use));
        } catch (Horde_Imap_Client_Exception $e) {
            $notification->push(sprintf(_("The folder \"%s\" was not created. This is what the server said"), $folder->display) . ': ' . $e->getMessage(), 'horde.error');
            return false;
        }

        $GLOBALS['notification']->push(sprintf(_("The folder \"%s\" was successfully created."), $folder->display), 'horde.success');

        /* Subscribe, if requested. */
        if ($subscribe) {
            $this->subscribe(array($folder));
        }

        /* Update the mailbox tree. */
        $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->insert($folder);

        return true;
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
        $old = IMP_Mailbox::get($old);
        $new = IMP_Mailbox::get($new);

        /* Don't try to rename from or to an empty string. */
        if (!$old || !$new) {
            return false;
        }

        if (!$force && $old->fixed) {
            $GLOBALS['notification']->push(sprintf(_("The folder \"%s\" may not be renamed."), $old->display), 'horde.error');
            return false;
        }

        $deleted = array($old);
        $inserted = array($new);

        $all_folders = $this->getAllSubfolders($old);

        try {
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->renameMailbox($old, $new);
        } catch (Horde_Imap_Client_Exception $e) {
            $GLOBALS['notification']->push(sprintf(_("Renaming \"%s\" to \"%s\" failed. This is what the server said"), $old->display, $new->display) . ': ' . $e->getMessage(), 'horde.error');
            return false;
        }

        $GLOBALS['notification']->push(sprintf(_("The folder \"%s\" was successfully renamed to \"%s\"."), $old->display, $new->display), 'horde.success');

        foreach ($all_folders as $folder_old) {
            $deleted[] = $folder_old;

            /* Get the new folder name. */
            $inserted[] = $folder_new = substr_replace($folder_old, $new, 0, strlen($old));
        }

        if (!empty($deleted)) {
            $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->rename($deleted, $inserted);
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

        foreach (IMP_Mailbox::get($folders) as $folder) {
            try {
                $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->subscribeMailbox($folder, true);
                $notification->push(sprintf(_("You were successfully subscribed to \"%s\""), $folder->display), 'horde.success');
                $subscribed[] = $folder;
            } catch (Horde_Imap_Client_Exception $e) {
                $notification->push(sprintf(_("You were not subscribed to \"%s\". Here is what the server said"), $folder->display) . ': ' . $e->getMessage(), 'horde.error');
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

        foreach (IMP_Mailbox::get($folders) as $folder) {
            if ($folder->inbox) {
                $notification->push(sprintf(_("You cannot unsubscribe from \"%s\"."), $folder->display), 'horde.error');
            } else {
                try {
                    $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->subscribeMailbox($folder, false);
                    $notification->push(sprintf(_("You were successfully unsubscribed from \"%s\""), $folder->display), 'horde.success');
                    $unsubscribed[] = $folder;
                } catch (Horde_Imap_Client_Exception $e) {
                    $notification->push(sprintf(_("You were not unsubscribed from \"%s\". Here is what the server said"), $folder->display) . ': ' . $e->getMessage(), 'horde.error');
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
                $status = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->status($folder, Horde_Imap_Client::STATUS_MESSAGES);
            } catch (Horde_Imap_Client_Exception $e) {
                continue;
            }

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->envelope();
            $query->imapDate();
            $query->fullText(array(
                'peek' => true
            ));

            for ($i = 1; $i <= $status['messages']; ++$i) {
                /* Download one message at a time to save on memory
                 * overhead. */
                try {
                    $res = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->fetch($folder, $query, array(
                        'ids' => new Horde_Imap_Client_Ids($i, true)
                    ));
                    $ptr = reset($res);
                } catch (Horde_Imap_Client_Exception $e) {
                    continue;
                }

                $from = '<>';
                if ($from_env = $ptr->getEnvelope()->from) {
                    $ptr2 = reset($from_env);
                    if (!empty($ptr2['mailbox']) && !empty($ptr2['host'])) {
                        $from = $ptr2['mailbox']. '@' . $ptr2['host'];
                    }
                }

                /* We need this long command since some MUAs (e.g. pine)
                 * require a space in front of single digit days. */
                $imap_date = $ptr->getImapDate();
                $date = sprintf('%s %2s %s', $imap_date->format('D M'), $imap_date->format('j'), $imap_date->format('H:i:s Y'));
                fwrite($body, 'From ' . $from . ' ' . $date . "\r\n");
                stream_copy_to_stream($ptr->getFullMsg(true), $body);
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
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

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

    /**
     * Get list of all folders under a given mailbox.
     *
     * @param string $mbox           The base mailbox.
     * @param boolean $include_base  Include the base mailbox in results?
     *
     * @return array  All mailboxes under the base mailbox.
     */
    public function getAllSubfolders($mbox, $include_base = true)
    {
        $imaptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imaptree->setIteratorFilter(IMP_Imap_Tree::FLIST_NOCONTAINER | IMP_Imap_Tree::FLIST_UNSUB | IMP_Imap_Tree::FLIST_NOBASE, $mbox);

        $out = array_keys(iterator_to_array($imaptree));
        if ($include_base && $this->exists($mbox)) {
            $out = array_merge(array($mbox), $out);
        }

        return $out;
    }

}
