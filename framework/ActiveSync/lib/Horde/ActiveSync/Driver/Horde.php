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
     * Used for profiling
     *
     * @var timestamp
     */
    private $_starttime;

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
        $auth = Horde_Auth::singleton($GLOBALS['conf']['auth']['driver']);

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
        $supported = $this->_connector->horde_listApis();
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
                $events = $this->_connector->calendar_listEvents($startstamp, $endstamp, null);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return array();
            }
            foreach ($events as $day) {
                foreach($day as $e) {
                    $messages[] = $this->_smartStatMessage($folderid, $e->uid, false);
                }
            }
            break;

        case self::CONTACTS_FOLDER:
            try {
                $contacts = $this->_connector->contacts_list();
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return array();
            }

            foreach ($contacts as $contact) {
                $messages[] = $this->_smartStatMessage($folderid, $contact, true);
            }
            break;

        case self::TASKS_FOLDER:
            $tasks = $this->_connector->tasks_listTasks();
            foreach ($tasks as $task)
            {
                $messages[] = $this->_smartStatMessage($folderid, $task, true);
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
     * @param string $folderId    The server id of the collection to check.
     * @param timestamp $from_ts  The starting timestamp
     * @param timestamp $to_ts    The ending timestamp
     *
     * @return array A list of messge uids that have chnaged in the specified
     *               time period.
     */
    public function getServerChanges($folderId, $from_ts, $to_ts)
    {
        $adds = $this->_connector->calendar_listBy('add', $from_ts);
        $changes = $this->_connector->calendar_listBy('modify', $from_ts);
        $deletes = $this->_connector->calendar_listBy('delete', $from_ts);

        // FIXME: Need to filter the results by $from_ts OR need to fix
        // Horde_History to query for a timerange instead of a single timestamp

        return $changes;
    }

    /**
     * Get a message from the backend
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#getMessage($folderid, $id, $truncsize, $mimesupport)
     */
    public function getMessage($folderid, $id, $truncsize, $mimesupport = 0)
    {
        $this->_logger->debug('Horde::getMessage(' . $folderid . ', ' . $id . ')');

        $message = false;
        switch ($folderid) {
        case self::APPOINTMENTS_FOLDER:
            try {
                return $this->_connector->calendar_export($id);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return false;
            }
            break;

        case self::CONTACTS_FOLDER:
            try {
                return $this->_connector->contacts_export($id);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return false;
            }

            break;

        case self::TASKS_FOLDER:
            try {
                return $this->_connector->tasks_export($id);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                return false;
            }
            break;
        default:
            return false;
        }
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
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#DeleteMessage($folderid, $id)
     */
    public function DeleteMessage($folderid, $id)
    {
        $this->_logger->debug('Horde::DeleteMessage(' . $folderid . ', ' . $id . ')');

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
     * @param string $folderid
     * @param string $id
     * @param Horde_ActiveSync_Message_Base $message
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#changeMessage($folderid, $id, $message)
     */
    public function changeMessage($folderid, $id, $message)
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
                $stat = $this->_smartStatMessage($folderid, $id, false);
            } else {
                // ActiveSync messages do NOT contain the serverUID value, put
                // it in ourselves so we can have it during import/change.
                $message->setServerUID($id);
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
            } else {
                try {
                    $this->_connector->contacts_replace($id, $message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return false;
                }
                $stat = $this->_smartStatMessage($folderid, $id, false);
            }
        case self::TASKS_FOLDER:
            if (!$id) {
                try {
                    $id = $this->_connector->tasks_import($message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return false;
                }

                $stat = $this->_smartStatMessage($folderid, $id, false);
            } else {
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
     * @param string $searchquery
     *
     * @return array
     */
    public function getSearchResults($searchquery)
    {
        $this->_logger->err('getSearchResults not yet implemented');
        return array();
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
            $this->_modCache[$statKey] = $mod;
        }
        $message = array();
        $message['id'] = $id;
        $message['mod'] = $mod;
        $message['flags'] = 1;

        return $message;
    }

}
