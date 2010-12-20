<?php
/**
 * Hermes SQL storage driver.
 *
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Hermes
 */
class Hermes_Driver_Sql extends Hermes_Driver
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor
     *
     * @param array $params  A hash containing connection parameters.
     * <pre>
     *   db_adapter => The Horde_Db_Adapter object
     * </pre>
     *
     * @return Hermes_Driver_Sql  The driver object.
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
        if (empty($params['db_adapter'])) {
            throw new InvalidArgumentException('Missing Horde_Db_Adapter parameter.');
        }
        $this->_db = $params['db_adapter'];
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
     * @return integer  The new timeslice_id of the newly entered slice
     * @throws Hermes_Exception
     */
    public function enterTime($employee, array $info)
    {
        /* Get job rate */
        $sql = 'SELECT jobtype_rate FROM hermes_jobtypes WHERE jobtype_id = ?';
        try {
            $job_rate = $this->_db->selectValue($sql, array($info['type']));
        } catch (Horde_Db_Exception $e) {
            throw new Hermes_Exception($e);
        }
        $dt = new Date($info['date']);
        $sql = 'INSERT INTO hermes_timeslices (' .
               'clientjob_id, employee_id, jobtype_id, ' .
               'timeslice_hours, timeslice_isbillable, ' .
               'timeslice_date, timeslice_description, ' .
               'timeslice_note, timeslice_rate, costobject_id) ' .
               'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        
        $values = array($info['client'],
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

        try {
            return $this->_db->insert($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Hermes_Exception($e);
        }
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
     * @return mixed  boolean
     * @throws Horde_Exception_PermissionDenied
     * @throws Hermes_Exception
     */
    public function updateTime($entries)
    {
        foreach ($entries as $info) {
            if (!Hermes::canEditTimeslice($info['id'])) {
                throw new Horde_Exception_PermissionDenied(_("Access denied; user cannot modify this timeslice."));
            }
            if (!empty($info['delete'])) {
                try {
                    return $this->_db->delete('DELETE FROM hermes_timeslices WHERE timeslice_id = ?', array((int)$info['id']));
                } catch (Horde_Db_Exception $e) {
                    throw new Hermes_Exception($e);
                }
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
                try {
                    return $this->_db->update($sql, $values);
                } catch (Horde_Db_Exception $e) {
                    throw new Hermes_Exception($e);
                }
            }
        }
    }

    /**
     * @TODO
     *
     * @global <type> $conf
     * @param <type> $filters
     * @param <type> $fields
     * @return <type> 
     */
    function getHours(array $filters = array(), array $fields = array())
    {
        global $conf;

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
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . $where;
        }
        $sql .= ' ORDER BY timeslice_date DESC, clientjob_id';
        
        try {
            $hours = $this->_db->selectAll($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Hermes_Exception($e);
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
                    try {
                        $costobject = Hermes::getCostObjectByID($hours[$hkey]['costobject']);
                    } catch (Horde_Exception $e) {
                        $hours[$hkey]['_costobject_name'] = sprintf(_("Error: %s"), $e->getMessage());
                    }
                    $hours[$hkey]['_costobject_name'] = $costobject['name'];
                }
            }
        }

        return $hours;
    }

    /**
     * @TODO
     */
    private function _equalClause($lhs, $rhs, $quote = true)
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

    /**
     * @TODO
     *
     * @param <type> $field
     * @param <type> $hours
     * @return <type>
     */
    public function markAs($field, $hours)
    {
        if (!count($hours)) {
            return false;
        }

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

        return $this->_db->update($sql);
    }

    /**
     * @TODO
     *
     * @param <type> $criteria
     * @return <type>
     */
    public function listJobTypes(array $criteria = array())
    {
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

        try {
            $rows = $this->_db->selectAll($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Hermes_Exception($e);
        }

        $results = array();
        foreach ($rows as $row) {
            $id = $row['jobtype_id'];
            $results[$id] = array('id'       => $id,
                                  'name'     => $row['jobtype_name'],
                                  'rate'     => (float)$row['jobtype_rate'],
                                  'billable' => (int)$row['jobtype_billable'],
                                  'enabled'  => !empty($row['jobtype_enabled']));
        }

        return $results;
    }

    public function updateJobType($jobtype)
    {
        if (!isset($jobtype['enabled'])) {
            $jobtype['enabled'] = 1;
        }
        if (!isset($jobtype['billable'])) {
            $jobtype['billable'] = 1;
        }
        if (empty($jobtype['id'])) {
            $sql = 'INSERT INTO hermes_jobtypes (jobtype_name, jobtype_enabled, '
                . 'jobtype_rate, jobtype_billable) VALUES (?, ?, ?, ?)';
            $values = array($jobtype['name'],
                            (int)$jobtype['enabled'],
                            (float)$jobtype['rate'],
                            (int)$jobtype['billable']);

            try {
                return $this->_db->insert($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Hermes_Exception($e);
            }
        } else {
            $sql = 'UPDATE hermes_jobtypes' .
                   ' SET jobtype_name = ?, jobtype_enabled = ?, jobtype_rate = ?,' .
                   ' jobtype_billable = ?  WHERE jobtype_id = ?';
            $values = array($jobtype['name'],
                            (int)$jobtype['enabled'],
                            (float)$jobtype['rate'],
                            (int)$jobtype['billable'],
                            $jobtype['id']);

            try {
                $this->_db->update($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Hermes_Exception($e);
            }

            return $jobtype['id'];
        }
    }

    public function deleteJobType($jobTypeID)
    {
        try {
            return $this->_db->delete('DELETE FROM hermes_jobtypes WHERE jobtype_id = ?', array($jobTypeID));
        } catch (Horde_Db_Exception $e) {
            throw Hermes_Exception($e);
        }
    }

    /**
     * @see Hermes_Driver::updateDeliverable
     */
    public function updateDeliverable($deliverable)
    {
        if (empty($deliverable['id'])) {
            $sql = 'INSERT INTO hermes_deliverables (' .
                   ' client_id, deliverable_name, deliverable_parent,' .
                   ' deliverable_estimate, deliverable_active,' .
                   ' deliverable_description) VALUES (?, ?, ?, ?, ?, ?)';
            $values = array($deliverable['client_id'],
                            $deliverable['name'],
                            (empty($deliverable['parent']) ? null :
                             (int)$deliverable['parent']),
                            (empty($deliverable['estimate']) ? null :
                             $deliverable['estimate']),
                            ($deliverable['active'] ? 1 : 0),
                            (empty($deliverable['description']) ? null :
                             $deliverable['description']));

            try {
                return $this->_db->insert($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Hermes_Exception($e);
            }
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
            try {
                $this->_db->update($sql, $values);
                return $deliverable['id'];
            } catch (Horde_Db_Exception $e) {
                throw new Hermes_Exception($e);
            }
        }
    }

    /**
     * @see Hermes_Driver::listDeliverables()
     */
    public function listDeliverables($criteria = array())
    {
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

        try {
            $rows = $this->_db->selectAll($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Hermes_Exception($e);
        }

        $deliverables = array();
        //$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        //while (!empty($row) && !is_a($row, 'PEAR_Error')) {
        foreach ($rows as $row) {
            $deliverable = array('id'          => $row['deliverable_id'],
                                 'client_id'   => $row['client_id'],
                                 'name'        => $row['deliverable_name'],
                                 'parent'      => $row['deliverable_parent'],
                                 'estimate'    => $row['deliverable_estimate'],
                                 'active'      => !empty($row['deliverable_active']),
                                 'description' => $row['deliverable_description']);
            $deliverables[$row['deliverable_id']] = $deliverable;
        }

        return $deliverables;
    }

    /**
     * @see Hermes_Driver::updateDeliverable
     * @throws Hermes_Exception
     */
    public function deleteDeliverable($deliverableID)
    {
        $sql = 'SELECT COUNT(*) AS c FROM hermes_deliverables WHERE deliverable_parent = ?';
        $values = array($deliverableID);

        try {
            $result = $this->_db->selectValue($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Hermes_Exception($e);
        }
        if (!empty($result)) {
            throw new Hermes_Exception(_("Cannot delete deliverable; it has children."));
        }

        $sql = 'SELECT COUNT(*) AS c FROM hermes_timeslices WHERE costobject_id = ?';
        $values = array($deliverableID);
        try {
            $result = $this->_db->selectValue($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Hermes_Exception($e);
        }
        if (!empty($result)) {
            throw Hermes_Exception(_("Cannot delete deliverable; there is time entered on it."));
        }

        $sql = 'DELETE FROM hermes_deliverables WHERE deliverable_id = ?';
        $values = array($deliverableID);

        try {
            return $this->_db->delete($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Hermes_Exception($e);
        }
    }

    public function getClientSettings($clientID)
    {
        $clients = Hermes::listClients();
        if (empty($clientID) || !isset($clients[$clientID])) {
            throw new Hermes_Exception('Does not exist');
        }

        $sql = 'SELECT clientjob_id, clientjob_enterdescription,' .
               ' clientjob_exportid FROM hermes_clientjobs' .
               ' WHERE clientjob_id = ?';
        $values = array($clientID);

        try {
            $rows = $this->_db->selectAll($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Hermes_Exception($e);
        }
        $clientJob = array();
        foreach ($rows as $row) {
            $clientJob[$row['clientjob_id']] = array($row['clientjob_enterdescription'], $row['clientjob_exportid']);
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

    /**
     * @TODO
     *
     * @param <type> $clientID
     * @param <type> $enterdescription
     * @param string $exportid
     * @return <type>
     */
    public function updateClientSettings($clientID, $enterdescription = 1, $exportid = null)
    {
        if (empty($exportid)) {
            $exportid = null;
        }

        $sql = 'SELECT clientjob_id FROM hermes_clientjobs WHERE clientjob_id = ?';
        $values = array($clientID);

        if ($this->_db->selectValue($sql, $values) !== $clientID) {
            $sql = 'INSERT INTO hermes_clientjobs (clientjob_id,' .
                   ' clientjob_enterdescription, clientjob_exportid)' .
                   ' VALUES (?, ?, ?)';
            $values = array($clientID, (int)$enterdescription, $exportid);

            try {
                return $this->_db->insert($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Hermes_Exception($e);
            }
        } else {
            $sql = 'UPDATE hermes_clientjobs SET' .
                   ' clientjob_exportid = ?, clientjob_enterdescription = ?' .
                   ' WHERE clientjob_id = ?';
            $values = array($exportid, (int)$enterdescription, $clientID);
            
            try {
                return $this->_db->update($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Hermes_Exception($e);
            }
        }
    }

    /**
     * @TODO
     * @global  $conf
     * @return <type>
     */
    public function purge()
    {
        global $conf;

        $query = 'DELETE FROM hermes_timeslices' .
                 ' WHERE timeslice_exported = ? AND timeslice_date < ?';
        $values = array(1, mktime(0, 0, 0, date('n'),
                                  date('j') - $conf['time']['days_to_keep']));
        return $this->_db->delete($query, $values);
    }
    
}
