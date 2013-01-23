<?php
/**
 * @package Alarm
 *
 * Copyright 2007-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

/**
 * The Horde_Alarm_sql class is a Horde_Alarm storage implementation using the
 * PEAR DB package.
 *
 * The table structure can be created by the
 * horde/scripts/sql/horde_alarm.sql script.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Alarm
 */
class Horde_Alarm_Sql extends Horde_Alarm
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <pre>
     * 'db' - (Horde_Db_Adapter) [REQUIRED] The DB instance.
     * 'table' - (string) The name of the tokens table in 'database'.
     *           DEFAULT: 'horde_alarms'
     * </pre>
     *
     * @throws Horde_Alarm_Exception
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['db'])) {
            throw new Horde_Alarm_Exception('Missing db parameter.');
        }
        $this->_db = $params['db'];
        unset($params['db']);

        $params = array_merge(array(
            'table' => 'horde_alarms'
        ), $params);

        parent::__construct($params);
    }

    /**
     * Returns a list of alarms from the backend.
     *
     * @param string $user      Return alarms for this user, all users if
     *                          null, or global alarms if empty.
     * @param Horde_Date $time  The time when the alarms should be active.
     *
     * @return array  A list of alarm hashes.
     * @throws Horde_Alarm_Exception
     */
    protected function _list($user, Horde_Date $time)
    {
        $query = sprintf('SELECT alarm_id, alarm_uid, alarm_start, alarm_end, alarm_methods, alarm_params, alarm_title, alarm_text, alarm_snooze, alarm_internal FROM %s WHERE alarm_dismissed = 0 AND ((alarm_snooze IS NULL AND alarm_start <= ?) OR alarm_snooze <= ?) AND (alarm_end IS NULL OR alarm_end >= ?)%s ORDER BY alarm_start, alarm_end',
                         $this->_params['table'],
                         is_null($user) ? '' : ' AND (alarm_uid IS NULL OR alarm_uid = ? OR alarm_uid = ?)');
        $dt = $time->setTimezone('UTC')->format('Y-m-d\TH:i:s');
        $values = array($dt, $dt, $dt);
        if (!is_null($user)) {
            $values[] = '';
            $values[] = (string)$user;
        }


        try {
            $result = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Alarm_Exception($e);
        }

        $alarms = array();
        foreach ($result as $val) {
            $alarms[] = $this->_getHash($val);
        }

        return $alarms;
    }

    /**
     * Returns a list of all global alarms from the backend.
     *
     * @return array  A list of alarm hashes.
     * @throws Horde_Alarm_Exception
     */
    protected function _global()
    {
        $query = sprintf('SELECT alarm_id, alarm_uid, alarm_start, alarm_end, alarm_methods, alarm_params, alarm_title, alarm_text, alarm_snooze, alarm_internal FROM %s WHERE alarm_uid IS NULL OR alarm_uid = \'\' ORDER BY alarm_start, alarm_end',
                         $this->_params['table']);

        try {
            $result = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Alarm_Exception($e);
        }

        $alarms = array();
        foreach ($result as $val) {
            $alarms[] = $this->_getHash($val);
        }

        return $alarms;
    }

    /**
     */
    protected function _getHash(array $alarm)
    {
        $params = base64_decode($alarm['alarm_params']);
        if (!strlen($params) && strlen($alarm['alarm_params'])) {
            $params = $alarm['alarm_params'];
        }
        return array(
            'id' => $alarm['alarm_id'],
            'user' => $alarm['alarm_uid'],
            'start' => new Horde_Date($alarm['alarm_start'], 'UTC'),
            'end' => empty($alarm['alarm_end']) ? null : new Horde_Date($alarm['alarm_end'], 'UTC'),
            'methods' => @unserialize($alarm['alarm_methods']),
            'params' => @unserialize($params),
            'title' => $this->_fromDriver($alarm['alarm_title']),
            'text' => $this->_fromDriver($alarm['alarm_text']),
            'snooze' => empty($alarm['alarm_snooze']) ? null : new Horde_Date($alarm['alarm_snooze'], 'UTC'),
            'internal' => empty($alarm['alarm_internal']) ? null : @unserialize($alarm['alarm_internal'])
        );
    }

    /**
     * Returns an alarm hash from the backend.
     *
     * @param string $id    The alarm's unique id.
     * @param string $user  The alarm's user
     *
     * @return array  An alarm hash.
     * @throws Horde_Alarm_Exception
     */
    protected function _get($id, $user)
    {
        $query = sprintf('SELECT alarm_id, alarm_uid, alarm_start, alarm_end, alarm_methods, alarm_params, alarm_title, alarm_text, alarm_snooze, alarm_internal FROM %s WHERE alarm_id = ? AND %s',
                         $this->_params['table'],
                         !empty($user) ? 'alarm_uid = ?' : '(alarm_uid = ? OR alarm_uid IS NULL)');

        try {
            $alarm = $this->_db->selectOne($query, array($id, $user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Alarm_Exception($e);
        }

        if (empty($alarm)) {
            throw new Horde_Alarm_Exception('Alarm not found');
        }

        return $this->_getHash($alarm);
    }

    /**
     * Adds an alarm hash to the backend.
     *
     * @param array $alarm  An alarm hash.
     *
     * @throws Horde_Alarm_Exception
     */
    protected function _add(array $alarm)
    {
        $query = sprintf('INSERT INTO %s (alarm_id, alarm_uid, alarm_start, alarm_end, alarm_methods, alarm_params, alarm_title, alarm_text, alarm_snooze) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $this->_params['table']);
        $values = array(
            $alarm['id'],
            isset($alarm['user']) ? $alarm['user'] : '',
            (string)$alarm['start']->setTimezone('UTC'),
            empty($alarm['end']) ? null : (string)$alarm['end']->setTimezone('UTC'),
            serialize($alarm['methods']),
            base64_encode(serialize($alarm['params'])),
            $this->_toDriver($alarm['title']),
            empty($alarm['text']) ? null : $this->_toDriver($alarm['text']),
            null
        );

        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Alarm_Exception($e);
        }
    }

    /**
     * Updates an alarm hash in the backend.
     *
     * @param array $alarm         An alarm hash.
     * @param boolean $keepsnooze  Whether to keep the snooze value unchanged.
     *
     * @throws Horde_Alarm_Exception
     */
    protected function _update(array $alarm, $keepsnooze = false)
    {
        $query = sprintf('UPDATE %s set alarm_start = ?, alarm_end = ?, alarm_methods = ?, alarm_params = ?, alarm_title = ?, alarm_text = ?%s WHERE alarm_id = ? AND %s',
                         $this->_params['table'],
                         $keepsnooze ? '' : ', alarm_snooze = NULL, alarm_dismissed = 0',
                         isset($alarm['user']) ? 'alarm_uid = ?' : '(alarm_uid = ? OR alarm_uid IS NULL)');
        $values = array((string)$alarm['start']->setTimezone('UTC'),
                        empty($alarm['end']) ? null : (string)$alarm['end']->setTimezone('UTC'),
                        serialize($alarm['methods']),
                        base64_encode(serialize($alarm['params'])),
                        $this->_toDriver($alarm['title']),
                        empty($alarm['text'])
                              ? null
                              : $this->_toDriver($alarm['text']),
                        $alarm['id'],
                        isset($alarm['user']) ? $alarm['user'] : '');

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Alarm_Exception($e);
        }
    }

    /**
     * Updates internal alarm properties, i.e. properties not determined by
     * the application setting the alarm.
     *
     * @param string $id       The alarm's unique id.
     * @param string $user     The alarm's user
     * @param array $internal  A hash with the internal data.
     *
     * @throws Horde_Alarm_Exception
     */
    public function internal($id, $user, array $internal)
    {
        $query = sprintf('UPDATE %s set alarm_internal = ? WHERE alarm_id = ? AND %s',
                         $this->_params['table'],
                         !empty($user) ? 'alarm_uid = ?' : '(alarm_uid = ? OR alarm_uid IS NULL)');
        $values = array(serialize($internal), $id, $user);

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Alarm_Exception($e);
        }
    }

    /**
     * Returns whether an alarm with the given id exists already.
     *
     * @param string $id    The alarm's unique id.
     * @param string $user  The alarm's user
     *
     * @return boolean  True if the specified alarm exists.
     * @throws Horde_Alarm_Exception
     */
    protected function _exists($id, $user)
    {
        $query = sprintf('SELECT 1 FROM %s WHERE alarm_id = ? AND %s',
                         $this->_params['table'],
                         !empty($user) ? 'alarm_uid = ?' : '(alarm_uid = ? OR alarm_uid IS NULL)');

        try {
            return ($this->_db->selectValue($query, array($id, $user)) == 1);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Alarm_Exception($e);
        }
    }

    /**
     * Delays (snoozes) an alarm for a certain period.
     *
     * @param string $id          The alarm's unique id.
     * @param string $user        The alarm's user
     * @param Horde_Date $snooze  The snooze time.
     *
     * @throws Horde_Alarm_Exception
     */
    protected function _snooze($id, $user, Horde_Date $snooze)
    {
        $query = sprintf('UPDATE %s set alarm_snooze = ? WHERE alarm_id = ? AND %s',
                         $this->_params['table'],
                         !empty($user) ? 'alarm_uid = ?' : '(alarm_uid = ? OR alarm_uid IS NULL)');
        $values = array((string)$snooze->setTimezone('UTC'), $id, $user);

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Alarm_Exception($e);
        }
    }

    /**
     * Returns whether an alarm is snoozed.
     *
     * @param string $id        The alarm's unique id.
     * @param string $user      The alarm's user
     * @param Horde_Date $time  The time when the alarm may be snoozed.
     *
     * @return boolean  True if the alarm is snoozed.
     * @throws Horde_Alarm_Exception
     */
    protected function _isSnoozed($id, $user, Horde_Date $time)
    {
        $query = sprintf('SELECT 1 FROM %s WHERE alarm_id = ? AND %s AND (alarm_dismissed = 1 OR (alarm_snooze IS NOT NULL AND alarm_snooze >= ?))',
                         $this->_params['table'],
                         !empty($user) ? 'alarm_uid = ?' : '(alarm_uid = ? OR alarm_uid IS NULL)');

        try {
            return $this->_db->selectValue($query, array($id, $user, (string)$time->setTimezone('UTC')));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Alarm_Exception($e);
        }
    }

    /**
     * Dismisses an alarm.
     *
     * @param string $id          The alarm's unique id.
     * @param string $user        The alarm's user
     *
     * @throws Horde_Alarm_Exception
     */
    protected function _dismiss($id, $user)
    {
        $query = sprintf('UPDATE %s set alarm_dismissed = 1 WHERE alarm_id = ? AND %s',
                         $this->_params['table'],
                         !empty($user) ? 'alarm_uid = ?' : '(alarm_uid = ? OR alarm_uid IS NULL)');
        $values = array($id, $user);

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Alarm_Exception($e);
        }
    }

    /**
     * Deletes an alarm from the backend.
     *
     * @param string $id    The alarm's unique id.
     * @param string $user  The alarm's user. All users' alarms if null.
     *
     * @throws Horde_Alarm_Exception
     */
    protected function _delete($id, $user = null)
    {
        $query = sprintf('DELETE FROM %s WHERE alarm_id = ?', $this->_params['table']);
        $values = array($id);
        if (!is_null($user)) {
            $query .= empty($user)
                ? ' AND (alarm_uid IS NULL OR alarm_uid = ?)'
                : ' AND alarm_uid = ?';
            $values[] = $user;
        }

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Alarm_Exception($e);
        }
    }

    /**
     * Garbage collects old alarms in the backend.
     */
    protected function _gc()
    {
        $query = sprintf('DELETE FROM %s WHERE alarm_end IS NOT NULL AND alarm_end < ?', $this->_params['table']);
        $end = new Horde_Date(time());
        $this->_db->delete($query, array((string)$end->setTimezone('UTC')));
    }

    /**
     * Initialization tasks.
     *
     * @throws Horde_Alarm_Exception
     */
    public function initialize()
    {
        /* Handle any database specific initialization code to run. */
        switch ($this->_db->adapterName()) {
        case 'PDO_Oci':
            $query = "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'";
            $db->execute($query);
            break;

        case 'PDO_PostrgreSQL':
            $query = "SET datestyle TO 'iso'";
            $db->execute($query);
            break;
        }
    }

    /**
     * Converts a value from the driver's charset.
     *
     * @param mixed $value  Value to convert.
     *
     * @return mixed  Converted value.
     */
    protected function _fromDriver($value)
    {
        return Horde_String::convertCharset($value, $this->_params['charset'], 'UTF-8');
    }

    /**
     * Converts a value to the driver's charset.
     *
     * @param mixed $value  Value to convert.
     *
     * @return mixed  Converted value.
     */
    protected function _toDriver($value)
    {
        return Horde_String::convertCharset($value, 'UTF-8', $this->_params['charset']);
    }

}
