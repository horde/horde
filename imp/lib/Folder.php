<?php
/**
 * The IMP_Folder:: class provides a set of methods for dealing with folders,
 * accounting for subscription, errors, etc.
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
     * Working array for importMbox().
     *
     * @var array
     */
    protected $_import = array(
        'data' => array(),
        'msgs' => 0,
        'size' => 0
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
            } catch (IMP_Imap_Exception $e) {
                $e->notify(sprintf(_("The folder \"%s\" was not deleted. This is what the server said"), $folder->display) . ': ' . $e->getMessage());
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
     * 'special_use' - (array) An array of special-use attributes to attempt
     *                 to add to the mailbox.
     *                 DEFAULT: NONE
     * </pre>
     *
     * @return boolean  Whether or not the folder was successfully created.
     * @throws Horde_Exception
     */
    public function create($folder, $subscribe, array $opts = array())
    {
        global $conf, $injector, $notification;

        /* Check permissions. */
        $perms = $injector->getInstance('Horde_Core_Perms');
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
                sprintf(_("You are not allowed to create more than %d folders."), $injector->getInstance('Horde_Perms')->getPermissions('max_folders', $GLOBALS['registry']->getAuth()))
            );
            return false;
        }

        $folder = IMP_Mailbox::get($folder);

        /* Make sure we are not trying to create a duplicate folder */
        if ($folder->exists) {
            $notification->push(sprintf(_("The folder \"%s\" already exists."), $folder->display), 'horde.warning');
            return false;
        }

        /* Special use flags. */
        $special_use = isset($opts['special_use'])
            ? $opts['special_use']
            : array();

        /* Attempt to create the mailbox. */
        try {
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->createMailbox($folder, array('special_use' => $special_use));
        } catch (IMP_Imap_Exception $e) {
            if ($e->getCode() == Horde_Imap_Client_Exception::USEATTR) {
                return $this->create($folder, $subscribe);
            }

            $e->notify(sprintf(_("The folder \"%s\" was not created. This is what the server said"), $folder->display) . ': ' . $e->getMessage());
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

        $all_folders = $old->subfolders;

        try {
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->renameMailbox($old, $new);
        } catch (IMP_Imap_Exception $e) {
            $e->notify(sprintf(_("Renaming \"%s\" to \"%s\" failed. This is what the server said"), $old->display, $new->display) . ': ' . $e->getMessage());
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
            $notification->push(_("No folders were specified."), 'horde.warning');
            return false;
        }

        foreach (IMP_Mailbox::get($folders) as $folder) {
            try {
                $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->subscribeMailbox($folder, true);
                $notification->push(sprintf(_("You were successfully subscribed to \"%s\"."), $folder->display), 'horde.success');
                $subscribed[] = $folder;
            } catch (IMP_Imap_Exception $e) {
                $e->notify(sprintf(_("You were not subscribed to \"%s\". Here is what the server said"), $folder->display) . ': ' . $e->getMessage());
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
            $notification->push(_("No folders were specified."), 'horde.message');
            return false;
        }

        foreach (IMP_Mailbox::get($folders) as $folder) {
            if ($folder->inbox) {
                $notification->push(sprintf(_("You cannot unsubscribe from \"%s\"."), $folder->display), 'horde.error');
            } else {
                try {
                    $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->subscribeMailbox($folder, false);
                    $notification->push(sprintf(_("You were successfully unsubscribed from \"%s\"."), $folder->display), 'horde.success');
                    $unsubscribed[] = $folder;
                } catch (IMP_Imap_Exception $e) {
                    $e->notify(sprintf(_("You were not unsubscribed from \"%s\". Here is what the server said"), $folder->display) . ': ' . $e->getMessage());
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

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

        foreach ($folder_list as $folder) {
            try {
                $status = $imp_imap->status($folder, Horde_Imap_Client::STATUS_MESSAGES);
            } catch (IMP_Imap_Exception $e) {
                continue;
            }

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->size();

            try {
                $size = $imp_imap->fetch($folder, $query, array(
                    'ids' => new Horde_Imap_Client_Ids(Horde_Imap_Client_Ids::ALL, true)
                ));
            } catch (IMP_Imap_Exception $e) {
                continue;
            }

            $curr_size = 0;
            $start = 1;
            $slices = array();

            /* Handle 5 MB chunks of data at a time. */
            for ($i = 1; $i <= $status['messages']; ++$i) {
                $curr_size += $size[$i]->getSize();
                if ($curr_size > 5242880) {
                    $slices[] = new Horde_Imap_Client_Ids(range($start, $i), true);
                    $curr_size = 0;
                    $start = $i + 1;
                }
            }

            if ($start <= $status['messages']) {
                $slices[] = new Horde_Imap_Client_Ids(range($start, $status['messages']), true);
            }

            unset($size);

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->envelope();
            $query->imapDate();
            $query->fullText(array(
                'peek' => true
            ));

            foreach ($slices as $slice) {
                try {
                    $res = $imp_imap->fetch($folder, $query, array(
                        'ids' => $slice
                    ));
                } catch (IMP_Imap_Exception $e) {
                    continue;
                }

                reset($res);
                while (list(,$ptr) = each($res)) {
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
        }

        return $body;
    }

    /**
     * Imports messages into a given mailbox from a mbox (see RFC 4155) -or-
     * a message source (eml) file.
     *
     * @param string $mailbox  The mailbox to put the messages into
     *                         (UTF7-IMAP).
     * @param string $fname    Filename containing the message data.
     *
     * @return mixed  False (boolean) on fail or the number of messages
     *                imported (integer) on success.
     * @throws IMP_Exception
     */
    public function importMbox($mailbox, $fname, $type)
    {
        $fd = $format = $msg = null;

        if (!file_exists($fname)) {
            return false;
        }

        switch ($type) {
        case 'application/gzip':
        case 'application/x-gzip':
        case 'application/x-gzip-compressed':
            // No need to default to Horde_Compress because it uses zlib
            // also.
            if (in_array('compress.zlib', stream_get_wrappers())) {
                $fname = 'compress.zlib://' . $fname;
            }
            break;

        case 'application/x-bzip2':
        case 'application/x-bzip':
            if (in_array('compress.bzip2', stream_get_wrappers())) {
                $fname = 'compress.bzip2://' . $fname;
            }
            break;

        case 'application/zip':
        case 'application/x-compressed':
        case 'application/x-zip-compressed':
            if (in_array('zip', stream_get_wrappers())) {
                $fname = 'zip://' . $fname;
            } else {
                try {
                    $zip = Horde_Compress::factory('Zip');
                    if ($zip->canDecompress) {
                        $file_data = file_get_contents($fname);

                        $zip_info = $zip->decompress($file_data, array(
                            'action' => Horde_Compress_Zip::ZIP_LIST
                        ));

                        if (!empty($zip_info)) {
                            $fd = fopen('php://temp', 'r+');

                            foreach (array_keys($zip_info) as $key) {
                                fwrite($fd, $zip->decompress($file_data, array(
                                    'action' => Horde_Compress_Zip::ZIP_DATA,
                                    'info' => $zip_info,
                                    'key' => $key
                                )));
                            }

                            rewind($fd);
                        }
                    }
                } catch (Horde_Compress_Exception $e) {
                    if ($fd) {
                        fclose($fd);
                        $fd = null;
                    }
                }

                $fname = null;
            }
            break;
        }

        if (!is_null($fname)) {
            $fd = fopen($fname, 'r');
        }

        if (!$fd) {
            throw new IMP_Exception(_("The uploaded file cannot be opened"));
        }

        while (!feof($fd)) {
            $line = fgets($fd);

            /* RFC 4155 - mbox format. */
            // TODO: Better preg for matching From line
            // See(?) http://code.iamcal.com/php/rfc822/
            if ((!$format || ($format == 'mbox')) &&
                preg_match('/^From (.+@.+|- )/', $line)) {
                $format = 'mbox';

                if ($msg) {
                    /* Send in chunks to take advantage of MULTIAPPEND (if
                     * available). */
                    $this->_importMbox($msg, $mailbox, true);
                }

                $msg = fopen('php://temp', 'r+');
            } elseif ($msg) {
                fwrite($msg, $line);
            } elseif (!$format && trim($line)) {
                /* Allow blank space at beginning of file. Anything else is
                 * treated as message input. */
                $format = 'eml';
                $msg = fopen('php://temp', 'r+');
                fwrite($msg, $line);
            }
        }
        fclose($fd);

        if ($msg) {
            $this->_importMbox($msg, $mailbox);
        }

        return $this->_import['msgs']
            ? $this->_import['msgs']
            : false;
    }

    /**
     * Helper for importMbox().
     *
     * @param resource $msg    Stream containing message data.
     * @param string $mailbox  The mailbox to put the messages into
     *                         (UTF7-IMAP).
     * @param integer $buffer  Buffer messages before sending?
     */
    protected function _importMbox($msg, $mailbox, $buffer = false)
    {
        $this->_import['data'][] = array('data' => $msg);
        $this->_import['size'] += intval(ftell($msg));

        /* Buffer 5 MB of messages before sending. */
        if ($buffer && ($this->_import['size'] < 5242880)) {
            return;
        }

        try {
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->append($mailbox, $this->_import['data']);
            $this->_import['msgs'] += count($this->_import['data']);
        } catch (IMP_Imap_Exception $e) {}

        foreach ($this->_import['data'] as $val) {
            fclose($val['data']);
        }

        $this->_import['data'] = array();
        $this->_import['size'] = 0;
    }

}
