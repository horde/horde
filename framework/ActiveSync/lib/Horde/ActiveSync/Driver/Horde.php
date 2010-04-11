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
    public function Logoff()
    {
        $this->_logger->info('Horde_ActiveSync_Driver_Horde::logoff');
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
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#GetWasteBasket()
     */
    public function GetWasteBasket()
    {
        $this->_logger->debug('Horde::GetWasteBasket()');

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
            $folders[] = $this->StatFolder(self::APPOINTMENTS_FOLDER);
        }

        if (array_search('contacts', $supported)){
            $folders[] = $this->StatFolder(self::CONTACTS_FOLDER);
        }

        if (array_search('tasks', $supported)){
            $folders[] = $this->StatFolder(self::TASKS_FOLDER);
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
    public function StatFolder($id)
    {
        $this->_logger->debug('Horde::StatFolder(' . $id . ')');

        $folder = array();
        $folder['id'] = $id;
        $folder['mod'] = $id;
        $folder['parent'] = 0;

        return $folder;
    }

    /**
     * Get the message list of specified folder
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#GetMessageList($folderId, $cutOffDate)
     */
    public function GetMessageList($folderid, $cutoffdate)
    {
        $this->_logger->debug('Horde::GetMessageList(' . $folderid . ', ' . $cutoffdate . ')');

        $messages = array();
        switch ($folderid) {
        case self::APPOINTMENTS_FOLDER:
            $startstamp = (int)$cutoffdate;
            $endstamp = time() + 32140800; //60 * 60 * 24 * 31 * 12 == one year

            try {
                $events = $this->_connector->calendar_listEvents($startstamp, $endstamp, null);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->GetMessage());

                return false;
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
                $this->_logger->err($e->GetMessage());

                return false;
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
            return false;
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
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#GetMessage($folderid, $id, $truncsize, $mimesupport)
     */
    public function GetMessage($folderid, $id, $truncsize, $mimesupport = 0)
    {
        $this->_logger->debug('Horde::GetMessage(' . $folderid . ', ' . $id . ')');

        $message = false;
        switch ($folderid) {
        case self::APPOINTMENTS_FOLDER:
            try {
                return $this->_connector->calendar_export($id);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->GetMessage());
                return false;
            }
            break;

        case self::CONTACTS_FOLDER:
            try {
                $contact = $this->_connector->contacts_export($id, 'array');
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->GetMessage());
                return false;
            }

           return self::_fromHash($contact);
            break;

        case self::TASKS_FOLDER:
            try {
                return $this->_connector->tasks_export($id);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->GetMessage());
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
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#StatMessage($folderId, $id)
     */
    public function StatMessage($folderid, $id)
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
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#ChangeMessage($folderid, $id, $message)
     */
    public function ChangeMessage($folderid, $id, $message)
    {
        $this->_logger->debug('Horde::ChangeMessage(' . $folderid . ', ' . $id . ')');

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
            $content = self::_toHash($message);
            if (!$id) {
                try {
                    $id = $this->_connector->contacts_import($content, 'array');
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    return false;
                }
                $stat = $this->_smartStatMessage($folderid, $id, false);
            } else {
                try {
                    $this->_connector->contacts_replace($id, $content, 'array');
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

    /**
     * Create a hash suitable for importing into contacts/import or
     * contacts/replace from a Horde_ActiveSync_Message_Base object.
     *
     * @param Horde_ActiveSync_Message_Contact $message
     *
     * @return array
     */
    private static function _toHash(Horde_ActiveSync_Message_Contact $message)
    {
        $charset = Horde_Nls::getCharset();
        $formattedname = false;

        /* Name */
        $hash['name'] = Horde_String::convertCharset($message->fileas, 'utf-8', $charset);
        $hash['lastname'] = Horde_String::convertCharset($message->lastname, 'utf-8', $charset);
        $hash['firstname'] = Horde_String::convertCharset($message->firstname, 'utf-8', $charset);
        $hash['middlenames'] = Horde_String::convertCharset($message->middlename, 'utf-8', $charset);
        $hash['namePrefix'] = Horde_String::convertCharset($message->title, 'utf-8', $charset);
        $hash['nameSuffix'] = Horde_String::convertCharset($message->suffix, 'utf-8', $charset);


        // picture ($message->picture *should* already be base64 encdoed)
        $hash['photo'] = base64_decode($message->picture);

        /* Home */
        $hash['homeStreet'] = Horde_String::convertCharset($message->homestreet, 'utf-8', $charset);
        $hash['homeCity'] = Horde_String::convertCharset($message->homecity, 'utf-8', $charset);
        $hash['homeProvince'] = Horde_String::convertCharset($message->homestate, 'utf-8', $charset);
        $hash['homePostalCode'] = $message->homepostalcode;
        $hash['homeCountry'] = Horde_String::convertCharset($message->homecountry, 'utf-8', $charset);

        /* Business */
        $hash['workStreet'] = Horde_String::convertCharset($message->businessstreet, 'utf-8', $charset);
        $hash['workCity'] = Horde_String::convertCharset($message->businesscity, 'utf-8', $charset);
        $hash['workProvince'] = Horde_String::convertCharset($message->businessstate, 'utf-8', $charset);
        $hash['workPostalCode'] = $message->businesspostalcode;
        $hash['workCountry'] = Horde_String::convertCharset($message->businesscountry, 'utf-8', $charset);

        $hash['homePhone'] = $message->homephonenumber;
        $hash['workPhone'] = $message->businessphonenumber;
        $hash['fax'] = $message->businessfaxnumber;
        $hash['pager'] = $message->pagernumber;
        $hash['cellPhone'] = $message->mobilephonenumber;

        /* Email addresses */
        $hash['email'] = Horde_iCalendar_vcard::getBareEmail($message->email1address);

        /* Job title */
        $hash['title'] = Horde_String::convertCharset($message->jobtitle, 'utf-8', $charset);

        $hash['company'] = Horde_String::convertCharset($message->companyname, 'utf-8', $charset);
        $hash['department'] = Horde_String::convertCharset($message->department, 'utf-8', $charset);

        /* Categories */
        if (count($message->categories)) {
            $hash['category']['value'] = Horde_String::convertCharset(implode(';', $message->categories), 'utf-8', $charset);
            $hash['category']['new'] = true;
        }

        /* Children */
        // @TODO

        /* Spouse */
        $hash['spouse'] = Horde_String::convertCharset($message->spouse, 'utf-8', $charset);

        /* Notes */
        $hash['notes'] = Horde_String::convertCharset($message->body, 'utf-8', $charset);

        /* webpage */
        $hash['website'] = Horde_String::convertCharset($message->webpage, 'utf-8', $charset);

        /* Birthday and Anniversary */
        if (!empty($message->birthday)) {
            $bday = new Horde_Date($message->birthday);
            $hash['birthday'] = $bday->format('Y-m-d');
        } else {
            $hash['birthday'] = null;
        }
        if (!empty($message->anniversary)) {
            $anniversary = new Horde_Date($message->anniversary);
            $hash['anniversary'] = $anniversary->format('Y-m-d');
        } else {
            $hash['anniversary'] = null;
        }

        /* Assistant */
        $hash['assistant'] = Horde_String::convertCharset($message->assistantname, 'utf-8', $charset);

        return $hash;
    }

    /**
     * Import data from Horde's contacts API
     *
     * @param array $hash  A hash as returned from contacts/export
     *
     * @return Horde_ActiveSync_Message_Base object
     */
    private static function _fromHash($hash)
    {
        $message = new Horde_ActiveSync_Message_Contact();

        $charset = Horde_Nls::getCharset();

        foreach ($hash as $field => $value) {
            switch ($field) {
            case 'name':
                $message->fileas = Horde_String::convertCharset($value, $charset, 'utf-8');
                break;
            case 'lastname':
                $message->lastname = Horde_String::convertCharset($value, $charset, 'utf-8');
                break;
            case 'firstname':
                $message->firstname = Horde_String::convertCharset($value, $charset, 'utf-8');
                break;
            case 'middlenames':
                $message->middlename = Horde_String::convertCharset($value, $charset, 'utf-8');
                break;
            case 'namePrefix':
                $message->title = Horde_String::convertCharset($value, $charset, 'utf-8');
                break;
            case 'nameSuffix':
                $message->suffix = Horde_String::convertCharset($value, $charset, 'utf-8');
                break;

            case 'photo':
                $message->picture = base64_encode($value['load']['data']);
                break;

            /* Address (TODO: check for a single home/workAddress field instead) */
            case 'homeStreet':
                $message->homestreet = Horde_String::convertCharset($hash['homeStreet'], $charset, 'utf-8');
                break;
            case 'homeCity':
                $message->homecity = Horde_String::convertCharset($hash['homeCity'], $charset, 'utf-8');
                break;
            case 'homeProvince':
                $message->homestate = Horde_String::convertCharset($hash['homeProvince'], $charset, 'utf-8');
                break;
            case 'homePostalCode':
                $message->homepostalcode = Horde_String::convertCharset($hash['homePostalCode'], $charset, 'utf-8');
                break;
            case 'homeCountry':
                $message->homecountry = Horde_String::convertCharset($hash['homeCountry'], $charset, 'utf-8');
                break;
            case 'workStreet':
                $message->businessstreet = Horde_String::convertCharset($hash['workStreet'], $charset, 'utf-8');
                break;
            case 'workCity':
                $message->businesscity = Horde_String::convertCharset($hash['workCity'], $charset, 'utf-8');
                break;
            case 'workProvince':
                $message->businessstate = Horde_String::convertCharset($hash['workProvince'], $charset, 'utf-8');
                break;
            case 'workPostalCode':
                $message->businesspostalcode = Horde_String::convertCharset($hash['workPostalCode'], $charset, 'utf-8');
                break;
            case 'workCountry':
                $message->businesscountry = Horde_String::convertCharset($hash['workCountry'], $charset, 'utf-8');
                break;
            case 'homePhone':
                /* Phone */
                $message->homephonenumber = $hash['homePhone'];
                break;
            case 'cellPhone':
                $message->mobilephonenumber = $hash['cellPhone'];
                break;
            case 'fax':
                $message->businessfaxnumber = $hash['fax'];
                break;
            case 'workPhone':
                $message->businessphonenumber = $hash['workPhone'];
                break;
            case 'pager':
                $message->pagernumber = $hash['pager'];
                break;

            case 'email':
                $message->email1address = Horde_iCalendar_vcard::getBareEmail($value);
                break;

            case 'title':
                $message->jobtitle = Horde_String::convertCharset($value, $charset, 'utf-8');
                break;

            case 'company':
                $message->companyname = Horde_String::convertCharset($value, $charset, 'utf-8');
                break;
            case 'departnemt':
                $message->department = Horde_String::convertCharset($value, $charset, 'utf-8');
                break;

            case 'category':
                // Categories FROM horde are a simple string value, going BACK to horde are an array with 'value' and 'new' keys
                $message->categories = explode(';', Horde_String::convertCharset($value, $charset, 'utf-8'));
                break;

            case 'spouse':
                $message->spouse = Horde_String::convertCharset($value, $charset, 'utf-8');
                break;
            case 'notes':
                $message->body = Horde_String::convertCharset($value, $charset, 'utf-8');
                $message->bodysize = strlen($message->body);
                $message->bodytruncated = false;
                break;
            case 'website':
                $message->webpage = $value;
                break;

            case 'birthday':
            case 'anniversary':
                if (!empty($value)) {
                    $date = new Horde_Date($value);
                    $message->{$field} = $date;
                } else {
                    $message->$field = null;
                }
                break;
            }
        }

        return $message;
    }

}
