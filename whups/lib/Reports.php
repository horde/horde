<?php
/**
 * Whups_Reports:: class.
 *
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */
class Whups_Reports {

    var $_backend;

    var $_opentickets;

    var $_closedtickets;

    var $_alltickets;

    function Whups_Reports($whups_driver)
    {
        $this->_backend = $whups_driver;
    }

    function getDataSet($report)
    {
        $operation = 'inc';
        $state = null;
        list($type, $field) = explode('|', $report);
        if (substr($type, 0, 1) == '@') {
            list($type, $operation, $state) = explode(':', substr($type, 1));
        }
        $tickets = &$this->_getTicketSet($type, ($field == 'owner'));

        if (substr($field, 0, 7) == 'user_id' || $field == 'owner') {
            $user = true;
        } else {
            $user = false;
        }

        $dataset = array();
        foreach ($tickets as $info) {
            switch ($state) {
            case 'open':
                require_once 'Date/Calc.php';

                $date1 = getdate($info['date_resolved']);
                $date2 = getdate($info['timestamp']);
                $newdata = Date_Calc::dateDiff($date1['mday'],
                                               $date1['mon'],
                                               $date1['year'],
                                               $date2['mday'],
                                               $date2['mon'],
                                               $date2['year']);
                break;

            default:
                $newdata = 1;
            }

            if (empty($info[$field])) {
                $this->_updateDataSet($dataset, _("None"), $newdata, $operation);
            } else {
                if ($user) {
                    $col = Whups::formatUser($info[$field], false);
                } else {
                    $col = $info[$field];
                }

                $this->_updateDataSet($dataset, $col, $newdata, $operation);
            }
        }

        // Perform any necessary post-processing on the dataset - process
        // averages, for example.
        switch ($operation) {
        case 'avg':
            foreach ($dataset as $index => $data) {
                $dataset[$index] = number_format(array_sum($data) / count($data), 2);
            }
            break;
        }

        // Sort
        ksort($dataset);

        // Return the final data.
        return $dataset;
    }

    function _updateDataSet(&$dataset, $index, $newdata, $operation)
    {
        if (isset($dataset[$index])) {
            switch ($operation) {
            case 'inc':
                $dataset[$index] += $newdata;
                break;

            case 'max':
            case 'min':
                $dataset[$index] = $operation($newdata, $dataset[$index]);
                break;

            case 'avg':
                $dataset[$index][] = $newdata;
                break;
            }
        } else {
            switch ($operation) {
            case 'avg':
                $dataset[$index] = array($newdata);
                break;

            default:
                $dataset[$index] = $newdata;
            }
        }
    }

    /**
     * Returns a time (max, min, avg) that tickets are in a particular state
     * (open, assigned, etc.).
     *
     * @param string $operation  One of 'max', 'min', or 'avg'.
     * @param string $state      The state to measure - 'open', etc.
     * @param string $group_by   A ticket property by which to group the
     *                           results.
     *
     * @return integer|array  The time value requested, or an array of values,
     *                        if the $group_by parameter has been specified.
     */
    function getTime($stat, $group_by = null)
    {
        list($operation, $state) = explode('|', $stat);

        $tickets = &$this->_getTicketSet('closed');
        if (!count($tickets)) {
            return PEAR::raiseError(_("There is no data for this report."));
        }

        $dataset = array();
        if (empty($group_by)) {
            $dataset[0] = array();
        }
        foreach ($tickets as $info) {
            if (is_null($info['date_resolved'])) {
                continue;
            }

            switch ($state) {
            case 'open':
                require_once 'Date/Calc.php';

                $date1 = getdate($info['date_resolved']);
                $date2 = getdate($info['timestamp']);
                $diff = Date_Calc::dateDiff($date1['mday'],
                                            $date1['mon'],
                                            $date1['year'],
                                            $date2['mday'],
                                            $date2['mon'],
                                            $date2['year']);
                if (empty($group_by)) {
                    $dataset[0][] = $diff;
                } else {
                    if (!isset($info[$group_by])) {
                        continue;
                    }
                    if (!isset($dataset[$info[$group_by]])) {
                        $dataset[$info[$group_by]] = array();
                    }
                    $dataset[$info[$group_by]][] = $diff;
                }

                break;
            }
        }

        if (!count($dataset) || (is_null($group_by) && !count($dataset[0]))) {
            return 'N/A';
        }

        switch ($operation) {
        case 'min':
        case 'max':
            foreach (array_keys($dataset) as $group) {
                $dataset[$group] = $operation($dataset[$group]);
            }
            break;

        case 'avg':
            foreach (array_keys($dataset) as $group) {
                $dataset[$group] = round(array_sum($dataset[$group]) / count($dataset[$group]), 2);
            }
            break;
        }

        if (empty($group_by)) {
            $dataset = $dataset[0];
        }

        return $dataset;
    }

    /**
     * Loads a set of tickets, and cache the result inside the Whups_Reports::
     * object to save on database access.
     *
     * @access private
     *
     * @param string $type       'open', 'closed', or 'all' - the set of
     *                           tickets to fetch. A previously cached set
     *                           will be returned if it is available.
     * @param boolean $expanded  List tickets once for each owner of the
     *                           ticket?
     *
     * @return array  A reference to the cached ticket set.
     */
    function &_getTicketSet($type, $expanded = false)
    {
        $queues = array_keys(Whups::permissionsFilter($this->_backend->getQueues(), 'queue'));
        $expanded = (int)$expanded;
        switch ($type) {
        case 'open':
            if (is_null($this->_opentickets[$expanded])) {
                $this->_opentickets[$expanded] = $this->_backend->getTicketsByProperties(array('nores' => true, 'queue' => $queues), true, $expanded);
            }
            return $this->_opentickets[$expanded];

        case 'closed':
            if (is_null($this->_closedtickets[$expanded])) {
                $this->_closedtickets[$expanded] = $this->_backend->getTicketsByProperties(array('res' => true, 'queue' => $queues), true, $expanded);
            }
            return $this->_closedtickets[$expanded];

        case 'all':
            if (is_null($this->_alltickets[$expanded])) {
                $this->_alltickets[$expanded] = $this->_backend->getTicketsByProperties(array('queue' => $queues), true, $expanded);
            }
            return $this->_alltickets[$expanded];
        }
    }

}
