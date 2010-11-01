<?php

require_once HYLAX_BASE . '/lib/SQL/Attributes.php';

/**
 * Hylax_Storage_sql Class
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @package Hylax
 */
class Hylax_Storage_sql extends Hylax_Storage {

    /**
     * Handle for the database connection.
     *
     * @var DB
     */
    var $_db;

    /**
     * @var Hylax_SQL_Attributes
     */
    var $_attributes;

    function Hylax_Storage_sql($params)
    {
        parent::Hylax_Storage($params);
        $this->initialise();

        /* Set up the storage attributes object in the $_attributes var. */
        $attrib_params = array('primary_table'   => 'hylax_faxes',
                               'attribute_table' => 'hylax_fax_attributes',
                               'id_column'       => 'fax_id');
        $this->_attributes = new Hylax_SQL_Attributes($this->_db, $attrib_params);
    }

    function newFaxId()
    {
        $id = $this->_db->nextId('hylax_faxes');
        if (is_a($id, 'PEAR_Error')) {
            Horde::logMessage('Could not generate new fax id. %s' . $id->getMessage(), 'ERR');
        } else {
            Horde::logMessage('Generated new fax id: ' . $id, 'DEBUG');
        }
        return $id;
    }

    function _createFax(&$info)
    {
        /* Save to SQL. */
        $sql = 'INSERT INTO hylax_faxes (fax_id, fax_type, fax_user, fax_number, fax_pages, fax_created, fax_folder) VALUES (?, ?, ?, ?, ?, ?, ?)';
        $values = array($info['fax_id'],
                       (int)$info['fax_type'],
                       $info['fax_user'],
                       $info['fax_number'],
                       $info['fax_pages'],
                       $info['fax_created'],
                       $info['fax_folder']);
        Horde::logMessage('SQL Query by Hylax_Storage_sql::_createFax(): ' . $sql, 'DEBUG');
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
        }
        return $result;
    }

    function _listFaxes($folder)
    {
        $sql = 'SELECT * FROM hylax_faxes WHERE fax_folder = ?';
        $values = array($folder);
        $faxes = $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
        return $faxes;
    }

    function getFax($fax_id)
    {
        $sql = 'SELECT * FROM hylax_faxes WHERE fax_id = ?';
        $values = array($fax_id);
        $fax = $this->_db->getRow($sql, $values, DB_FETCHMODE_ASSOC);
        if (empty($fax)) {
            return PEAR::raiseError(_("No such fax found."));
        }
        return $fax;
    }

    function getFaxFolder($fax_id)
    {
        $sql = 'SELECT fax_folder FROM hylax_faxes WHERE fax_id = ?';
        $values = array($fax_id);
        $fax_folder = $this->_db->getOne($sql, $values);
        if (empty($fax_folder)) {
            return PEAR::raiseError(_("No such fax found."));
        }
        return $fax_folder;
    }

    function _setFaxNumber($fax_id, $number)
    {
        $sql = 'UPDATE hylax_faxes SET fax_number = ? WHERE fax_id = ?';
        $values = array($number, (int)$fax_id);
        return $this->_db->query($sql, $values);
    }

    function _setJobId($fax_id, $job_id)
    {
        $sql = 'UPDATE hylax_faxes SET job_id = ? WHERE fax_id = ?';
        $values = array((int)$job_id, (int)$fax_id);
        return $this->_db->query($sql, $values);
    }

    function _getFolder($folder, $path = null)
    {
        switch ($folder) {
        case 'inbox':
            //return $this->_parseFaxStat($this->_exec('faxstat -r'));
            break;

        case 'outbox':
            return $this->_parseFaxStat($this->_exec('faxstat -s'));
            break;

        case 'archive':
            //return $GLOBALS['storage']->getFolder($path);
            break;
        }
    }

    /**
     * Fetches a list of available gateways.
     *
     * @return array  An array of the available gateways.
     */
    function getGateways()
    {
        /* Get the gateways. */
        $sql = 'SELECT * FROM swoosh_gateways';
        Horde::logMessage('SQL Query by Hylax_Storage_sql::_getGateways(): ' . $sql, 'DEBUG');
        $result = $this->_db->getAll($sql, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
        }

        return $result;
    }

    /**
     * Fetches a gateway from the backend.
     *
     * @param int $gateway_id  The gateway id to fetch.
     *
     * @return array  An array containing the gateway settings and parameters.
     */
    function &getGateway($gateway_id)
    {
        /* Get the gateways. */
        $sql = 'SELECT * FROM swoosh_gateways WHERE gateway_id = ?';
        $values = array((int)$gateway_id);
        Horde::logMessage('SQL Query by Hylax_Storage_sql::getGateway(): ' . $sql, 'DEBUG');
        $gateway = $this->_db->getRow($sql, $values, DB_FETCHMODE_ASSOC);
        if (is_a($gateway, 'PEAR_Error')) {
            Horde::logMessage($gateway, 'ERR');
            return $gateway;
        }

        /* Unserialize the gateway params. */
        $gateway['gateway_params'] = Horde_Serialize::unserialize($gateway['gateway_params'], Horde_Serialize::UTF7_BASIC);

        /* Unserialize the gateway send params. */
        $gateway['gateway_sendparams'] = Horde_Serialize::unserialize($gateway['gateway_sendparams'], Horde_Serialize::UTF7_BASIC);

        return $gateway;
    }

    /**
     * Saves a gateway to the backend.
     *
     * @param array $info  The gateway settings to be saved passed by reference
     *                     as an array.
     *
     * @return mixed  Gateway id on success or a PEAR error on failure.
     */
    function saveGateway(&$info)
    {
        if (empty($info['gateway_id'])) {
            /* No existing gateway id, so new gateway and get next id. */
            $info['gateway_id'] = $this->_db->nextId('swoosh_gateways');
            if (is_a($info['gateway_id'], 'PEAR_Error')) {
                Horde::logMessage($info['gateway_id'], 'ERR');
                return $info['gateway_id'];
            }
            $sql = 'INSERT INTO swoosh_gateways (gateway_id, gateway_driver, gateway_name, gateway_params, gateway_sendparams) VALUES (?, ?, ?, ?, ?)';
            $values = array();
        } else {
            /* Existing gateway id, so editing an existing gateway. */
            $sql_sprintf = 'UPDATE swoosh_gateways SET gateway_id = ?, gateway_driver = ?, gateway_name = ?, gateway_params = ?, gateway_sendparams = ? WHERE gateway_id = ?';
            $values = array((int)$info['gateway_id']);
        }

        /* Serialize the gateway params. */
        if (!empty($info['gateway_params'])) {
            $info['gateway_params'] = Horde_Serialize::serialize($info['gateway_params'], Horde_Serialize::UTF7_BASIC);
        } else {
            $info['gateway_params'] = 'NULL';
        }

        /* Serialize the gateway send params. */
        if (!empty($info['gateway_sendparams'])) {
            $info['gateway_sendparams'] = Horde_Serialize::serialize($info['gateway_sendparams'], Horde_Serialize::UTF7_BASIC);
        } else {
            $info['gateway_sendparams'] = 'NULL';
        }

        /* Put together the sql statement. */
        array_unshift($values,
                      (int)$info['gateway_id'],
                      $info['gateway_driver'],
                      $info['gateway_name'],
                      $info['gateway_params'],
                      $info['gateway_sendparams']);
        Horde::logMessage('SQL Query by Hylax_Storage_sql::saveGateway(): ' . $sql, 'DEBUG');
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return $info['gateway_id'];
    }

    /**
     * Deletes a gateway from the backend.
     *
     * @param int $gateway_id  The gateway id of the gateway to delete.
     *
     * @return mixed  True on success or a PEAR error on failure.
     */
    function deleteGateway($gateway_id)
    {
        $sql = 'DELETE FROM swoosh_gateways WHERE gateway_id = ?';
        $values = array((int)$gateway_id);
        Horde::logMessage('SQL Query by Hylax_Storage_sql::deleteGateway(): ' . $sql, 'DEBUG');
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
        }

        return $result;
    }

    /**
     * Saves a message to the backend.
     *
     * @param integer $gateway_id       The id of the gateway used to send this
     *                                  message.
     * @param string $message_text      The text of the message.
     * @param string $message_params    Any send params used for this message.
     * @param string $message_batch_id  If batch sending is used, the batch id
     *                                  of this message.
     *
     * @return mixed  True on success or PEAR Error on failure.
     */
    function saveMessage($gateway_id, $message_text, $message_params, $message_batch_id = null)
    {
        $message_id = $this->_db->nextId('swoosh_messages');
        if (is_a($message_id, 'PEAR_Error')) {
            Horde::logMessage($message_id, 'ERR');
            return $message_id;
        }

        /* Serialize the message params. */
        $message_params = Horde_Serialize::serialize($message_params, Horde_Serialize::UTF7_BASIC);

        $sql = 'INSERT INTO swoosh_messages (message_id, user_uid, gateway_id, message_batch_id, message_text, message_params, message_submitted) VALUES (?, ?, ?, ?, ?, ?, ?)';
        $values = array((int)$message_id,
                        $GLOBALS['registry']->getAuth(),
                        (int)$gateway_id,
                        is_null($message_batch_id) ? 'NULL' : (int)$message_batch_id,
                        $message_text,
                        $message_params,
                        (int)time());
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return $message_id;
    }

    /**
     * Saves an individual send to the backend. This will be one instance of
     * a message being sent to a recipient.
     *
     * @param int    $message_id  The message id.
     * @param string $remote_id   The text of the message.
     * @param string $recipient   Any send params used for this
     * @param string $error       Any send params used for this
     *
     * @return mixed  The send id on success or PEAR Error on failure.
     */
    function saveSend($message_id, $remote_id, $recipient, $error)
    {
        $send_id = $this->_db->nextId('swoosh_sends');
        if (is_a($send_id, 'PEAR_Error')) {
            Horde::logMessage($send_id, 'ERR');
            return $send_id;
        }
        $sql = 'INSERT INTO swoosh_sends (send_id, message_id, send_remote_id, send_recipient, send_status) VALUES (?, ?, ?, ?, ?)';
        $values = array($send_id,
                        $message_id,
                        (is_null($remote_id) ? 'NULL' : $remote_id),
                        $recipient,
                        (is_null($error) ? 'NULL' : $error));
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return $send_id;
    }

    /**
     * Gets all the messages for the current user.
     *
     * @access private
     *
     * @return mixed  The array of messages on success or PEAR Error on failure.
     */
    function _getMessages()
    {
        $sql = 'SELECT * FROM swoosh_messages WHERE user_uid = ?';
        $values = array($GLOBALS['registry']->getAuth());
        Horde::logMessage('SQL Query by Hylax_Storage_sql::_getMessages(): ' . $sql, 'DEBUG');
        $result = $this->_db->getAssoc($sql, false, $values, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
        }

        return $result;
    }

    /**
     * Fetches all sends for one or more message ids.
     *
     * @access private
     * @param int|array  $message_id  The message id(s).
     *
     * @return mixed  The send id on success or PEAR Error on failure.
     */
    function _getSends($message_ids)
    {
        if (!is_array($message_ids)) {
            $message_ids = array($message_ids);
        }

        $sql = 'SELECT message_id, swoosh_sends.* FROM swoosh_sends' .
               ' WHERE message_id IN (' . str_repeat('?, ', count($message_ids) - 1) . '?)';
        $values = $message_ids;
        Horde::logMessage('SQL Query by Hylax_Storage_sql::_getSends(): ' . $sql, 'DEBUG');
        $result = $this->_db->getAssoc($sql, false, $values, DB_FETCHMODE_ASSOC, true);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
        }

        return $result;
    }

    function initialise()
    {
        $this->_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_DbPear')->create('rw', 'hylax', 'sql');

        return true;
    }

}
