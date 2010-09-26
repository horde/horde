<?php
/**
 * Horde backend. Provides the communication between horde data and
 * ActiveSync server.  Some code based on an implementation found on Z-Push's
 * fourm. Original header appears below. All other changes are:
 *
 * Copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
/***********************************************
* File      :   horde.php
* Project   :   Z-Push
* Descr     :   Horde backend
* Created   :   09.03.2009
*
* � Holger de Carne holger@carne.de
* This file is distributed under GPL v2.
* Consult LICENSE file for details
************************************************/
class Horde_ActiveSync_Driver_Horde extends Horde_ActiveSync_Driver_Base
{
    /** Constants **/
    const APPOINTMENTS_FOLDER = 'Calendar';
    const CONTACTS_FOLDER = 'Contacts';
    const TASKS_FOLDER = 'Tasks';

    /**
     * Cache message stats
     *
     * @var Array of stat hashes
     */
    private $_modCache;

    /**
     * Horde connector instance
     *
     * @var Horde_ActiveSync_Driver_Horde_Connector_Registry
     */
    private $_connector;

    /**
     * Const'r
     *
     * @param array $params  Configuration parameters.
     *
     * @return Horde_ActiveSync_Driver_Horde
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
        if (empty($this->_params['connector'])) {
            throw new Horde_ActiveSync_Exception('Missing required connector object.');
        }
        $this->_connector = $params['connector'];
    }

    /**
     * Authenticate to Horde
     *
     * @TODO: Need to inject the auth handler (waiting for rpc.php refactor)
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#Logon($username, $domain, $password)
     */
    public function logon($username, $password, $domain = null)
    {
        $this->_logger->info('Horde_ActiveSync_Driver_Horde::logon attempt for: ' . $username);
        parent::logon($username, $password, $domain);
        $auth = $GLOBALS['injector']->getInstance('Horde_Auth')->getAuth();

        return $auth->authenticate($username, array('password' => $password));
    }

    /**
     * Clean up
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#Logoff()
     */
    public function logOff()
    {
        $this->_logger->info('User ' . $this->_user . ' logged off');
        return true;
    }

    /**
     * Setup sync parameters. The user provided here is the user the backend
     * will sync with. This allows you to authenticate as one user, and sync as
     * another, if the backend supports this.
     *
     * @param string $user      The username to sync as on the backend.
     *
     * @return boolean
     */
    public function setup($user)
    {
        parent::setup($user);
        $this->_modCache = array();
        return true;
    }

    /**
     * @TODO
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#getWasteBasket()
     */
    public function getWasteBasket()
    {
        $this->_logger->debug('Horde::getWasteBasket()');

        return false;
    }

    /**
     * Return a list of available folders
     *
     * @return array  An array of folder stats
     */
    public function getFolderList()
    {
        $this->_logger->debug('Horde::getFolderList()');
        /* Make sure we have the APIs needed for each folder class */
        try {
            $supported = $this->_connector->horde_listApis();
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            return array();
        }
        $folders = array();

        if (array_search('calendar', $supported)){
            $folders[] = $this->statFolder(self::APPOINTMENTS_FOLDER);
        }

        if (array_search('contacts', $supported)){
            $folders[] = $this->statFolder(self::CONTACTS_FOLDER);
        }

        if (array_search('tasks', $supported)){
            $folders[] = $this->statFolder(self::TASKS_FOLDER);
        }

        return $folders;
    }

    /**
     * Retrieve folder
     *
     * @param string $id  The folder id
     *
     * @return Horde_ActiveSync_Message_Folder
     */
    public function getFolder($id)
    {
        $this->_logger->debug('Horde::getFolder(' . $id . ')');

        $folder = new Horde_ActiveSync_Message_Folder();
        $folder->serverid = $id;
        $folder->parentid = "0";
        $folder->displayname = $id;

        switch ($id) {
        case self::APPOINTMENTS_FOLDER:
            $folder->type = Horde_ActiveSync::FOLDER_TYPE_APPOINTMENT;
            break;
        case self::CONTACTS_FOLDER:
            $folder->type = Horde_ActiveSync::FOLDER_TYPE_CONTACT;
            break;
        case self::TASKS_FOLDER:
            $folder->type = Horde_ActiveSync::FOLDER_TYPE_TASK;
            break;
        default:
            return false;
        }

        return $folder;
    }

    /**
     * Stat folder
     *
     * @param $id
     *
     * @return a stat hash
     */
    public function statFolder($id)
    {
        $this->_logger->debug('Horde::statFolder(' . $id . ')');

        $folder = array();
        $folder['id'] = $id;
        $folder['mod'] = $id;
        $folder['parent'] = 0;

        return $folder;
    }

    /**
     * Get the message list of specified folder
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#getMessageList($folderId, $cutOffDate)
     */
    public function getMessageList($folderid, $cutoffdate)
    {
        $this->_logger->debug('Horde::getMessageList(' . $folderid . ', ' . $cutoffdate . ')');

        $messages = array();
        switch ($folderid) {
        case self::APPOINTMENTS_FOLDER:
            $startstamp = (int)$cutoffdate;
            $endstamp = time() + 32140800; //60 * 60 * 24 * 31 * 12 == one year

            try {
                $events = $this->_connector->calendar_listUids($startstamp, $endstamp);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return array();
            }
            foreach ($events as $uid) {
                $messages[] = $this->_smartStatMessage($folderid, $uid, false);
            }
            break;

        case self::CONTACTS_FOLDER:
            try {
                $contacts = $this->_connector->contacts_listUids();
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return array();
            }

            foreach ($contacts as $contact) {
                $messages[] = $this->_smartStatMessage($folderid, $contact, false);
            }
            break;

        case self::TASKS_FOLDER:
            try {
                $tasks = $this->_connector->tasks_listUids();
            } catch (Horde_Exception $e) {
               $this->_logger->err($e->getMessage());
               return array();
            }
            foreach ($tasks as $task)
            {
                $messages[] = $this->_smartStatMessage($folderid, $task, false);
            }
            break;

        default:
            return array();
        }

        return $messages;
    }

    /**
     * Get a list of server changes that occured during the specified time
     * period.
     *
     * @param string $folderId  The server id of the collection to check.
     * @param integer $from_ts  The starting timestamp
     * @param integer $to_ts    The ending timestamp
     *
     * @return array A list of messge uids that have chnaged in the specified
     *               time period.
     */
    public function getServerChanges($folderId, $from_ts, $to_ts, $cutoffdate)
    {
        $this->_logger->debug("Horde_ActiveSync_Driver_Horde::getServerChanges($folderId, $from_ts, $to_ts, $cutoffdate)");
        $adds = array();

        switch ($folderId) {
        case self::APPOINTMENTS_FOLDER:
            if ($from_ts == 0) {
                /* Can't use History if it's a first sync */
                $startstamp = (int)$cutoffdate;
                $endstamp = time() + 32140800; //60 * 60 * 24 * 31 * 12 == one year
                try {
                    $adds = $this->_connector->calendar_listUids($startstamp, $endstamp);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return array();
                }
                $edits = $deletes = array();
            } else {
                try {
                      $changes = $this->_connector->calendar_getChanges($from_ts, $to_ts);
                      // @TODO: these assignments are just until all collections are refactored.
                      $adds = $changes['add'];
                      $edits = $changes['modify'];
                      $deletes = $changes['delete'];
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return array();
                }
            }
            break;
        case self::CONTACTS_FOLDER:
            /* Can't use History for first sync */
            if ($from_ts == 0) {
                try {
                    $adds = $this->_connector->contacts_listUids();
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return array();
                }
                $edits = $deletes = array();
            } else {
                try {
                    $adds = $this->_connector->contacts_listBy('add', $from_ts, $to_ts);
                    $edits = $this->_connector->contacts_listBy('modify', $from_ts, $to_ts);
                    $deletes = $this->_connector->contacts_listBy('delete', $from_ts, $to_ts);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return array();
                }
            }
            break;
        case self::TASKS_FOLDER:
            /* Can't use History for first sync */
            if ($from_ts == 0) {
                try {
                    $adds = $this->_connector->tasks_listUids();
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return array();
                }
                $edits = $deletes = array();
            } else {
                try {
                    $adds = $this->_connector->tasks_listBy('add', $from_ts, $to_ts);
                    $edits = $this->_connector->tasks_listBy('modify', $from_ts, $to_ts);
                    $deletes = $this->_connector->tasks_listBy('delete', $from_ts, $to_ts);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return array();
                }
            }
            break;
        }

        /* Build the changes array */
        $changes = array();

        /* Server additions */
        foreach ($adds as $add) {
            $changes[] = array(
                'id' => $add,
                'type' => 'change',
                'flags' => Horde_ActiveSync::FLAG_NEWMESSAGE);
        }

        /* Server changes */
        foreach ($edits as $change) {
            $changes[] = array(
                'id' => $change,
                'type' => 'change');

        }

        /* Server Deletions */
        foreach ($deletes as $deleted) {
            $changes[] = array(
                'id' => $deleted,
                'type' => 'delete');
        }

        return $changes;
    }

    /**
     * Get a message from the backend
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#getMessage
     */
    public function getMessage($folderid, $id, $truncsize, $mimesupport = 0)
    {
        $this->_logger->debug('Horde::getMessage(' . $folderid . ', ' . $id . ')');

        $message = false;
        switch ($folderid) {
        case self::APPOINTMENTS_FOLDER:
            try {
                $message = $this->_connector->calendar_export($id);
                // Nokia MfE requires the optional UID element.
                if (!$message->getUid()) {
                    $message->setUid(pack("H*" , md5($id)));
                }
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return false;
            }
            break;

        case self::CONTACTS_FOLDER:
            try {
                $message = $this->_connector->contacts_export($id);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return false;
            }
            break;

        case self::TASKS_FOLDER:
            try {
                $message = $this->_connector->tasks_export($id);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return false;
            }
            break;
        default:
            return false;
        }
        if (strlen($message->body) > $truncsize) {
            $message->body = self::truncate($message->body, $truncsize);
            $message->bodytruncated = 1;
        } else {
            // Be certain this is set.
            $message->bodytruncated = 0;
        }

        return $message;
    }

    /**
     * Get message stat data
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#statMessage($folderId, $id)
     */
    public function statMessage($folderid, $id)
    {
        return $this->_smartStatMessage($folderid, $id, true);
    }

    /**
     * Delete a message
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#deleteMessage($folderid, $id)
     */
    public function deleteMessage($folderid, $id)
    {
        $this->_logger->debug('Horde::deleteMessage(' . $folderid . ', ' . $id . ')');

        $status = false;
        switch ($folderid) {
        case self::APPOINTMENTS_FOLDER:
            try {
                $status = $this->_connector->calendar_delete($id);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return false;
            }
            break;

        case self::CONTACTS_FOLDER:
            try {
                $status = $this->_connector->contacts_delete($id);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return false;
            }
            break;

        case self::TASKS_FOLDER:
            try {
                $status = $this->_connector->tasks_delete($id);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return false;
            }
            break;
        default:
            return false;
        }

        return $status;
    }

    /**
     * Add/Edit a message
     *
     * @param string $folderid  The server id for the folder the message belongs
     *                          to.
     * @param string $id        The server's uid for the message if this is a
     *                          change to an existing message.
     * @param Horde_ActiveSync_Message_Base $message  The activesync message
     * @param object $device  The device information
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#changeMessage($folderid, $id, $message)
     */
    public function changeMessage($folderid, $id, $message, $device)
    {
        $this->_logger->debug('Horde::changeMessage(' . $folderid . ', ' . $id . ')');

        $stat = false;
        switch ($folderid) {
        case self::APPOINTMENTS_FOLDER:
            if (!$id) {
                try {
                    $id = $this->_connector->calendar_import($message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return false;
                }
                /* There is no history entry for new messages, so use the
                 * current time for purposes of remembering this is from the PIM
                 */
                $stat = $this->_smartStatMessage($folderid, $id, false);
                $stat['mod'] = time();
            } else {
                // ActiveSync messages do NOT contain the serverUID value, put
                // it in ourselves so we can have it during import/change.
                $message->setServerUID($id);
                if (!empty($device->supported[self::APPOINTMENTS_FOLDER])) {
                    $message->setSupported($device->supported[self::APPOINTMENTS_FOLDER]);
                }
                try {
                    $this->_connector->calendar_replace($id, $message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return false;
                }
                $stat = $this->_smartStatMessage($folderid, $id, false);
            }
            break;

        case self::CONTACTS_FOLDER:
            if (!$id) {
                try {
                    $id = $this->_connector->contacts_import($message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return false;
                }
                $stat = $this->_smartStatMessage($folderid, $id, false);
                $stat['mod'] = time();
            } else {
                if (!empty($device->supported[self::CONTACTS_FOLDER])) {
                    $message->setSupported($device->supported[self::CONTACTS_FOLDER]);
                }
                try {
                    $this->_connector->contacts_replace($id, $message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return false;
                }
                $stat = $this->_smartStatMessage($folderid, $id, false);
            }
            break;

        case self::TASKS_FOLDER:
            if (!$id) {
                try {
                    $id = $this->_connector->tasks_import($message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return false;
                }
                $stat = $this->_smartStatMessage($folderid, $id, false);
                $stat['mod'] = time();
            } else {
                if (!empty($device->supported[self::TASKS_FOLDER])) {
                    $message->setSupported($device->supported[self::TASKS_FOLDER]);
                }
                try {
                    $this->_connector->tasks_replace($id, $message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return false;
                }
                $stat = $this->_smartStatMessage($folderid, $id, false);
            }

            break;
        default:
            return false;
        }

        return $stat;
    }

    /**
     * Returns array of items which contain contact information
     *
     * @param string $query  The text string to match against any textual ANR
     *                       (Automatic Name Resolution) properties. Exchange's
     *                       searchable ANR properties are currently:
     *                       firstname, lastname, alias, displayname, email
     * @param string $range  The range to return (for example, 1-50).
     *
     * @return array with 'rows' and 'range' keys
     */
    public function getSearchResults($query, $range)
    {
        /* Get results */
        $return = array('rows' => array(),
                        'range' => $range);
        try {
            $results = $this->_connector->contacts_search($query);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_logger->err($e->getMessage());
            return $return;
        }

        /* Honor range, and don't bother if no results */
        $count = count($results);
        if (!$count) {
            return $return;
        }
        $this->_logger->info('Horde::getSearchResults found ' . $count . ' matches.');

        preg_match('/(.*)\-(.*)/', $range, $matches);
        $return_count = $matches[2] - $matches[1];
        $rows = array_slice($results, $matches[1], $return_count + 1, true);
        $rows = array_pop($rows);
        foreach ($rows as $row) {
            $return['rows'][] = array(
                Horde_ActiveSync::GAL_ALIAS => $row['alias'],
                Horde_ActiveSync::GAL_DISPLAYNAME => $row['name'],
                Horde_ActiveSync::GAL_EMAILADDRESS => $row['email'],
                Horde_ActiveSync::GAL_FIRSTNAME => $row['firstname'],
                Horde_ActiveSync::GAL_LASTNAME => $row['lastname'],
                Horde_ActiveSync::GAL_COMPANY => $row['company'],
                Horde_ActiveSync::GAL_HOMEPHONE => $row['homePhone'],
                Horde_ActiveSync::GAL_PHONE => $row['workPhone']
            );
        }

        return $return;
    }

    /**
     * Sends the email represented by the rfc822 string received by the PIM.
     * Currently only used when meeting requests are sent from the PIM.
     *
     * @param string $rfc822    The rfc822 mime message
     * @param boolean $forward  @TODO
     * @param boolean $reply    @TODO
     * @param boolean $parent   @TODO
     *
     * @return boolean
     */
    public function sendMail($rfc822, $forward = false, $reply = false, $parent = false)
    {
        $headers = Horde_Mime_Headers::parseHeaders($rfc822);
        $part = Horde_Mime_Part::parseMessage($rfc822);

        $mail = new Horde_Mime_Mail();
        $mail->addHeaders($headers->toArray());

        $body_id = $part->findBody();
        if ($body_id) {
            $body = $part->getPart($body_id);
            $body = $body->getContents();
            $mail->setBody($body);
        } else {
            $mail->setBody('No body?');
        }
        foreach ($part->contentTypeMap() as $id => $type) {
            $mail->addPart($type, $part->getPart($id)->toString());
        }

        $mail->send($this->_params['mail']);

        return true;
    }

    /**
     *
     * @param string $folderid  The folder id
     * @param string $id        The message id
     * @param mixed $hint       @TODO: Figure out what, exactly, this does :)
     *
     * @return message stat hash
     */
    private function _smartStatMessage($folderid, $id, $hint)
    {
        $this->_logger->debug('ActiveSync_Driver_Horde::_smartStatMessage:' . $folderid . ':' . $id);
        $statKey = $folderid . $id;
        $mod = false;
        if ($hint !== false && isset($this->_modCache[$statKey])) {
            $mod = $this->_modCache[$statKey];
        } elseif (is_int($hint)) {
            $mod = $hint;
            $this->_modCache[$statKey] = $mod;
        } else {
            try {
                switch ($folderid) {
                case self::APPOINTMENTS_FOLDER:
                    $mod = $this->_connector->calendar_getActionTimestamp($id, 'modify');
                    break;
                case self::CONTACTS_FOLDER:
                    $mod = $this->_connector->contacts_getActionTimestamp($id, 'modify');
                    break;
                case self::TASKS_FOLDER:
                    $mod = $this->_connector->tasks_getActionTimestamp($id, 'modify');

                    break;
                default:
                    return false;
                }
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return array('id' => '', 'mod' => 0, 'flags' => 1);
            }
            $this->_modCache[$statKey] = $mod;
        }
        $message = array();
        $message['id'] = $id;
        $message['mod'] = $mod;
        $message['flags'] = 1;

        return $message;
    }

}
