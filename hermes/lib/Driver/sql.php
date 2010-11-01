<?php
/**
 * Hermes storage implementation for PHP's PEAR database abstraction layer.
 *
 * Required values for $params:<pre>
 *      'phptype'       The database type (e.g. 'pgsql', 'mysql', etc.).</pre>
 *
 * Required by some database implementations:<pre>
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'database'      The name of the database.
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'options'       Additional options to pass to the database.
 *      'tty'           The TTY on which to connect to the database.
 *      'port'          The port on which to connect to the database.</pre>
 *
 * The table structure can be created by the
 * scripts/drivers/hermes.sql script.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org
 * @package Hermes
 */
class Hermes_Driver_sql extends Hermes_Driver {

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    var $_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL server.
     *
     * @var boolean
     */
    var $_connected = false;

    /**
     * Constructs a new SQL storage object.
     *
     * @param string $user      The user who owns these billing.
     * @param array  $params    A hash containing connection parameters.
     */
    function Hermes_Driver_sql($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Save a row of billing information.
     *
     * @param string $employee  The Horde ID of the person who worked the
     *                          hours.
     * @param array $entries    The billing information to enter. Each array
     *                          row must contain the following entries:
     *             'date'         The day the hours were worked (ISO format)
     *             'client'       The id of the client the work was done for.
     *             'type'         The type of work done.
     *             'hours'        The number of hours worked
     *             'rate'         The hourly rate the work was done at.
     *             'billable'     (optional) Whether or not the work is
     *                            billable hours.
     *             'description'  A short description of the work.
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function enterTime($employee, $info)
    {
        require_once 'Date.php';

        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Get job rate */
        $sql = 'SELECT jobtype_rate FROM hermes_jobtypes WHERE jobtype_id = ?';
        $job_rate = $this->_db->getOne($sql, array($info['type']));

        $dt = new Date($info['date']);
        $timeslice_id = $this->_db->nextId('hermes_timeslices');
        $sql = 'INSERT INTO hermes_timeslices (timeslice_id, ' .
               'clientjob_id, employee_id, jobtype_id, ' .
               'timeslice_hours, timeslice_isbillable, ' .
               'timeslice_date, timeslice_description, ' .
               'timeslice_note, timeslice_rate, costobject_id) ' .
               'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $values = array((int)$timeslice_id,
                        $info['client'],
                        $employee,
                        $info['type'],
                        $info['hours'],
                        isset($info['billable']) ? (int)$info['billable'] : 0,
                        (int)$dt->getTime(DATE_FORMAT_UNIXTIME) + 1,
                        $info['description'],
                        $info['note'],
                        (float)$job_rate,
                        (empty($info['costobject']) ? null :
                         $info['costobject']));

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->query($sql, $values);
    }

    /**
     * Update a set of billing information.
     *
     * @param array $entries  The billing information to enter. Each array row
     *                        must contain the following entries:
     *              'id'           The id of this time entry.
     *              'date'         The day the hours were worked (ISO format)
     *              'client'       The id of the client the work was done for.
     *              'type'         The type of work done.
     *              'hours'        The number of hours worked
     *              'rate'         The hourly rate the work was done at.
     *              'billable'     Whether or not the work is billable hours.
     *              'description'  A short description of the work.
     *
     *                        If any rows contain a 'delete' entry, those rows
     *                        will be deleted instead of updated.
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function updateTime($entries)
    {
        require_once 'Date.php';

        /* Make sure we have a valid database connection. */
        $this->_connect();

        foreach ($entries as $info) {
            if (!Hermes::canEditTimeslice($info['id'])) {
                return PEAR::raiseError(_("Access denied; user cannot modify this timeslice."));
            }
            if (!empty($info['delete'])) {
                $sql = 'DELETE FROM hermes_timeslices WHERE timeslice_id = ?';
                $values = array((int)$info['id']);
            } else {
                if (isset($info['employee'])) {
                    $employee_cl = ' employee_id = ?,';

                    $values = array($info['employee']);
                } else {
                    $employee_cl = '';
                }
                $dt = new Date($info['date']);
                $sql = 'UPDATE hermes_timeslices SET' . $employee_cl .
                       ' clientjob_id = ?, jobtype_id = ?,' .
                       ' timeslice_hours = ?, timeslice_isbillable = ?,' .
                       ' timeslice_date = ?, timeslice_description = ?,' .
                       ' timeslice_note = ?, costobject_id = ?' .
                       ' WHERE timeslice_id = ?';
                $values = array($info['client'],
                                $info['type'],
                                $info['hours'],
                                (isset($info['billable']) ? (int)$info['billable'] : 0),
                                (int)$dt->getTime(DATE_FORMAT_UNIXTIME) + 1,
                                $info['description'],
                                $info['note'],
                                (empty($info['costobject']) ? null : $info['costobject']),
                                (int)$info['id']);
            }

            Horde::logMessage($sql, 'DEBUG');

            $result = $this->_db->query($sql, $values);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
        }

        return true;
    }

    function getHours($filters = array(), $fields = array())
    {
        global $conf;

        /* Make sure we have a valid database connection. */
        $this->_connect();

        $fieldlist = array(
            'id' => 'b.timeslice_id as id',
            'client' => ' b.clientjob_id as client',
            'employee' => ' b.employee_id as employee',
            'type' => ' b.jobtype_id as type',
            '_type_name' => ' j.jobtype_name as "_type_name"',
            'hours' => ' b.timeslice_hours as hours',
            'rate' => ' b.timeslice_rate as rate',
            'billable' => empty($conf['time']['choose_ifbillable'])
                ? ' j.jobtype_billable as billable'
                : ' b.timeslice_isbillable as billable',
            'date' => ' b.timeslice_date as "date"',
            'description' => ' b.timeslice_description as description',
            'note' => ' b.timeslice_note as note',
            'submitted' => ' b.timeslice_submitted as submitted',
            'costobject' => ' b.costobject_id as costobject');
        if (!empty($fields)) {
            $fieldlist = array_keys(array_intersect(array_flip($fieldlist), $fields));
        }
        $fieldlist = implode(', ', $fieldlist);
        $sql = 'SELECT ' . $fieldlist . ' FROM hermes_timeslices b INNER JOIN hermes_jobtypes j ON b.jobtype_id = j.jobtype_id';
        if (count($filters) > 0) {
            $where = '';
            $glue = '';
            foreach ($filters as $field => $filter) {
                switch ($field) {
                case 'client':
                    $where .= $glue . $this->_equalClause('b.clientjob_id',
                                                          $filter);
                    $glue = ' AND';
                    break;

                case 'jobtype':
                    $where .= $glue . $this->_equalClause('b.jobtype_id',
                                                          $filter);
                    $glue = ' AND';
                    break;

                case 'submitted':
                    $where .= $glue . ' timeslice_submitted = ' . (int)$filter;
                    $glue = ' AND';
                    break;

                case 'exported':
                    $where .= $glue . ' timeslice_exported = ' . (int)$filter;
                    $glue = ' AND';
                    break;

                case 'billable':
                    $where .= $glue
                        . (empty($conf['time']['choose_ifbillable'])
                           ? ' jobtype_billable = '
                           : ' timeslice_isbillable = ')
                        . (int)$filter;
                    $glue = ' AND';
                    break;

                case 'start':
                    $where .= $glue . ' timeslice_date >= ' . (int)$filter;
                    $glue = ' AND';
                    break;

                case 'end':
                    $where .= $glue . ' timeslice_date <= ' . (int)$filter;
                    $glue = ' AND';
                    break;

                case 'employee':
                    $where .= $glue . $this->_equalClause('employee_id',
                                                          $filter);
                    $glue = ' AND';
                    break;

                case 'id':
                    $where .= $glue . $this->_equalClause('timeslice_id',
                                                          (int)$filter, false);
                    $glue = ' AND';
                    break;

                case 'costobject':
                    $where .= $glue . $this->_equalClause('costobject_id',
                                                          $filter);
                    $glue = ' AND';
                    break;
                }
            }
        if (!empty($where)) {
            $sql .= ' WHERE ' . $where;
        }
        }

        $sql .= ' ORDER BY timeslice_date DESC, clientjob_id';

        Horde::logMessage($sql, 'DEBUG');
        $hours = $this->_db->getAll($sql, DB_FETCHMODE_ASSOC);
        if (is_a($hours, 'PEAR_Error')) {
            return $hours;
        }

        // Do per-record processing
        foreach (array_keys($hours) as $hkey) {
            // Convert timestamps to Horde_Date objects
            $hours[$hkey]['date'] = new Horde_Date($hours[$hkey]['date']);

            // Add cost object names to the results.
            if (empty($fields) || in_array('costobject', $fields)) {
            
                if (empty($hours[$hkey]['costobject'])) {
                    $hours[$hkey]['_costobject_name'] = '';
                } else {
                    $costobject = Hermes::getCostObjectByID($hours[$hkey]['costobject']);
                    if (is_a($costobject, 'PEAR_Error')) {
                        $hours[$hkey]['_costobject_name'] = sprintf(_("Error: %s"), $costobject->getMessage());
                    } else {
                        $hours[$hkey]['_costobject_name'] = $costobject['name'];
                    }
                }
            }
        }

        return $hours;
    }

    /**
     * @access private
     */
    function _equalClause($lhs, $rhs, $quote = true)
    {
        require_once 'Horde/SQL.php';

        if (!is_array($rhs)) {
            if ($quote) {
                return sprintf(' %s = %s', $lhs, $this->_db->quote($rhs));
            }
            return sprintf(' %s = %s', $lhs, $rhs);
        }

        if (count($rhs) == 0) {
            return ' FALSE';
        }

        $glue = '';
        $ret = sprintf(' %s IN ( ', $lhs);
        foreach ($rhs as $value) {
            $ret .= $glue . $this->_db->quote($value);
            $glue = ', ';
        }
        return $ret . ' )';
    }

    function markAs($field, $hours)
    {
        if (!count($hours)) {
            return false;
        }

        $this->_connect();

        switch ($field) {
        case 'submitted':
            $h_field = 'timeslice_submitted';
            break;

        case 'exported':
            $h_field = 'timeslice_exported';
            break;

        default:
            return false;
        }

        $ids = array();
        foreach ($hours as $entry) {
            $ids[] = (int)$entry['id'];
        }

        $sql = 'UPDATE hermes_timeslices SET ' . $h_field . ' = 1' .
               ' WHERE timeslice_id IN (' . implode(',', $ids) . ')';

        Horde::logMessage($sql, 'DEBUG');

        return $this->_db->query($sql);
    }

    function listJobTypes($criteria = array())
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        $where = array();
        $values = array();
        if (isset($criteria['id'])) {
            $where[] = 'jobtype_id = ?';
            $values[] = $criteria['id'];
        }
        if (isset($criteria['enabled'])) {
            $where[] = 'jobtype_enabled = ?';
            $values[] = ($criteria['enabled'] ? 1 : 0);
        }

        $sql = 'SELECT jobtype_id, jobtype_name, jobtype_enabled' .
               ', jobtype_rate, jobtype_billable FROM hermes_jobtypes' .
               (empty($where) ? '' : (' WHERE ' . join(' AND ', $where))) .
               ' ORDER BY jobtype_name';

        Horde::logMessage($sql, 'DEBUG');

        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $results = array();
        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        while (!empty($row) && !is_a($row, 'PEAR_Error')) {
            $id = $row['jobtype_id'];
            $results[$id] = array('id'       => $id,
                                  'name'     => $row['jobtype_name'],
                                  'rate'     => (float)$row['jobtype_rate'],
                                  'billable' => (int)$row['jobtype_billable'],
                                  'enabled'  => !empty($row['jobtype_enabled']));
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        }
        if (is_a($row, 'PEAR_Error')) {
            return $row;
        }

        return $results;
    }

    function updateJobType($jobtype)
    {
        $this->_connect();

        if (!isset($jobtype['enabled'])) {
            $jobtype['enabled'] = 1;
        }
        if (!isset($jobtype['billable'])) {
            $jobtype['billable'] = 1;
        }
        if (empty($jobtype['id'])) {
            $jobtype['id'] = $this->_db->nextId('hermes_jobtypes');
            $sql = 'INSERT INTO hermes_jobtypes (jobtype_id, jobtype_name, ' .
                   ' jobtype_enabled, jobtype_rate, jobtype_billable) VALUES (?, ?, ?, ?, ?)';
            $values = array($jobtype['id'], $jobtype['name'],
                            (int)$jobtype['enabled'], (float)$jobtype['rate'], (int)$jobtype['billable']);
        } else {
            $sql = 'UPDATE hermes_jobtypes' .
                   ' SET jobtype_name = ?, jobtype_enabled = ?, jobtype_rate = ?,' .
                   ' jobtype_billable = ?  WHERE jobtype_id = ?';
            $values = array($jobtype['name'], (int)$jobtype['enabled'],(float)$jobtype['rate'],
                            (int)$jobtype['billable'], $jobtype['id']);
        }

        Horde::logMessage($sql, 'DEBUG');

        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return $jobtype['id'];
    }

    function deleteJobType($jobTypeID)
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        $sql = 'DELETE FROM hermes_jobtypes WHERE jobtype_id = ?';
        $values = array($jobTypeID);

        Horde::logMessage($sql, 'DEBUG');

        return $this->_db->query($sql, $values);
    }

    function updateDeliverable($deliverable)
    {
        $this->_connect();

        if (empty($deliverable['id'])) {
            $deliverable['id'] = $this->_db->nextId('hermes_deliverables');
            $sql = 'INSERT INTO hermes_deliverables (deliverable_id,' .
                   ' client_id, deliverable_name, deliverable_parent,' .
                   ' deliverable_estimate, deliverable_active,' .
                   ' deliverable_description) VALUES (?, ?, ?, ?, ?, ?, ?)';
            $values = array((int)$deliverable['id'],
                            $deliverable['client_id'],
                            $deliverable['name'],
                            (empty($deliverable['parent']) ? null :
                             (int)$deliverable['parent']),
                            (empty($deliverable['estimate']) ? null :
                             $deliverable['estimate']),
                            ($deliverable['active'] ? 1 : 0),
                            (empty($deliverable['description']) ? null :
                             $deliverable['description']));
        } else {
            $sql = 'UPDATE hermes_deliverables SET client_id = ?,' .
                   ' deliverable_name = ?, deliverable_parent = ?,' .
                   ' deliverable_estimate = ?, deliverable_active = ?,' .
                   ' deliverable_description = ? WHERE deliverable_id = ?';
            $values = array($deliverable['client_id'],
                            $deliverable['name'],
                            (empty($deliverable['parent']) ? null :
                             (int)$deliverable['parent']),
                            (empty($deliverable['estimate']) ? null :
                             $deliverable['estimate']),
                            ($deliverable['active'] ? 1 : 0),
                            (empty($deliverable['description']) ? null :
                             $deliverable['description']),
                            $deliverable['id']);
        }

        Horde::logMessage($sql, 'DEBUG');

        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return $deliverable['id'];
    }

    function listDeliverables($criteria = array())
    {
        $this->_connect();

        $where = array();
        $values = array();
        if (isset($criteria['id'])) {
            $where[] = 'deliverable_id = ?';
            $values[] = $criteria['id'];
        }
        if (isset($criteria['client_id'])) {
            $where[] = 'client_id = ?';
            $values[] = $criteria['client_id'];
        }
        if (isset($criteria['active'])) {
            if ($criteria['active']) {
                $where[] = 'deliverable_active <> ?';
            } else {
                $where[] = 'deliverable_active = ?';
            }
            $values[] = 0;
        }

        $sql = 'SELECT * FROM hermes_deliverables' .
               (count($where) ? ' WHERE ' . join(' AND ', $where) : '');

        Horde::logMessage($sql, 'DEBUG');

        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $deliverables = array();
        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        while (!empty($row) && !is_a($row, 'PEAR_Error')) {

            $deliverable = array('id'          => $row['deliverable_id'],
                                 'client_id'   => $row['client_id'],
                                 'name'        => $row['deliverable_name'],
                                 'parent'      => $row['deliverable_parent'],
                                 'estimate'    => $row['deliverable_estimate'],
                                 'active'      => !empty($row['deliverable_active']),
                                 'description' => $row['deliverable_description']);
            $deliverables[$row['deliverable_id']] = $deliverable;
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        }

        if (is_a($row, 'PEAR_Error')) {
            return $row;
        }

        return $deliverables;
    }

    function deleteDeliverable($deliverableID)
    {
        $this->_connect();

        $sql = 'SELECT COUNT(*) AS c FROM hermes_deliverables' .
               ' WHERE deliverable_parent = ?';
        $values = array($deliverableID);

        Horde::logMessage($sql, 'DEBUG');

        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if (!empty($row['c'])) {
            return PEAR::raiseError(_("Cannot delete deliverable; it has children."));
        }

        $sql = 'SELECT COUNT(*) AS c FROM hermes_timeslices' .
               ' WHERE costobject_id = ?';
        $values = array($deliverableID);

        Horde::logMessage($sql, 'DEBUG');

        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if (!empty($row['c'])) {
            return PEAR::raiseError(_("Cannot delete deliverable; there is time entered on it."));
        }

        $sql = 'DELETE FROM hermes_deliverables WHERE deliverable_id = ?';
        $values = array($deliverableID);

        Horde::logMessage($sql, 'DEBUG');

        return $this->_db->query($sql, $values);
    }

    function getClientSettings($clientID)
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        $clients = Hermes::listClients();
        if (empty($clientID) || !isset($clients[$clientID])) {
            return PEAR::raiseError('Does not exist');
        }

        $sql = 'SELECT clientjob_id, clientjob_enterdescription,' .
               ' clientjob_exportid FROM hermes_clientjobs' .
               ' WHERE clientjob_id = ?';
        $values = array($clientID);

        Horde::logMessage($sql, 'DEBUG');

        $clientJob = $this->_db->getAssoc($sql, false, $values);
        if (is_a($clientJob, 'PEAR_Error')) {
            return $clientJob;
        }

        if (isset($clientJob[$clientID])) {
            $settings = array('id' => $clientID,
                              'enterdescription' => $clientJob[$clientID][0],
                              'exportid' => $clientJob[$clientID][1]);
        } else {
            $settings = array('id' => $clientID,
                              'enterdescription' => 1,
                              'exportid' => null);
        }

        $settings['name'] = $clients[$clientID];
        return $settings;
    }

    function updateClientSettings($clientID, $enterdescription = 1, $exportid = null)
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        if (empty($exportid)) {
            $exportid = null;
        }

        $sql = 'SELECT clientjob_id FROM hermes_clientjobs' .
               ' WHERE clientjob_id = ?';
        $values = array($clientID);

        Horde::logMessage($sql, 'DEBUG');

        if ($this->_db->getOne($sql, $values) !== $clientID) {
            $sql = 'INSERT INTO hermes_clientjobs (clientjob_id,' .
                   ' clientjob_enterdescription, clientjob_exportid)' .
                   ' VALUES (?, ?, ?)';
            $values = array($clientID, (int)$enterdescription, $exportid);
        } else {
            $sql = 'UPDATE hermes_clientjobs SET' .
                   ' clientjob_exportid = ?, clientjob_enterdescription = ?' .
                   ' WHERE clientjob_id = ?';
            $values = array($exportid, (int)$enterdescription, $clientID);
        }

        Horde::logMessage($sql, 'DEBUG');

        return $this->_db->query($sql, $values);
    }

    function purge()
    {
        global $conf;

        /* Make sure we have a valid database connection. */
        $this->_connect();

        $query = 'DELETE FROM hermes_timeslices' .
                 ' WHERE timeslice_exported = ? AND timeslice_date < ?';
        $values = array(1, mktime(0, 0, 0, date('n'),
                                  date('j') - $conf['time']['days_to_keep']));
        return $this->_db->query($query, $values);
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success.
     * @throws Horde_Exception
     */
    function _connect()
    {
        if ($this->_connected) {
            return true;
        }

        $this->_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_DbPear')->create('rw', 'hermes', 'storage');

        return true;
    }

    /**
     * Disconnect from the SQL server and clean up the connection.
     *
     * @return boolean  True on success, false on failure.
     */
    function _disconnect()
    {
        if ($this->_connected) {
            $this->_connected = false;
            return $this->_db->disconnect();
        }

        return true;
    }

}
