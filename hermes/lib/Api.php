<?php
/**
 * Hermes external API interface.
 *
 * This file defines Hermes's external API interface. Other applications
 * can interact with Hermes through this API.
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @package Hermes
 */
class Hermes_Api extends Horde_Registry_Api
{
    /**
     * @TODO
     *
     * @param <type> $name
     * @param <type> $params
     *
     * @return <type>
     */
    public static function getTableMetaData($name, $params)
    {
        switch ($name) {
        case 'hours':
            $emptype = Hermes::getEmployeesType('enum');
            $clients = Hermes::listClients();
            $hours = $GLOBALS['injector']->getInstance('Hermes_Driver')->getHours($params);
            $yesno = array(1 => _("Yes"),
                           0 => _("No"));

            $columns = array(
                array('name'   => 'icons',
                      'title'  => '',
                      'type'   => '%html',
                      'nobr'   => true),
                array('name'   => 'checkbox',
                      'title'  => '',
                      'type'   => '%html',
                      'nobr'   => true),
                array('name'   => 'date',
                      'title'  => _("Date"),
                      'type'   => 'date',
                      'params' => array($GLOBALS['prefs']->getValue('date_format')),
                      'nobr'   => true),
                array('name'   => 'employee',
                      'title'  => _("Employee"),
                      'type'   => $emptype[0],
                      'params' => $emptype[1]),
                array('name'   => '_client_name',
                      'title'  => _("Client"),
                      'type'   => 'enum',
                      'params' => array($clients)),
                array('name'   => '_type_name',
                      'title'  => _("Job Type")),
                array('name'   => '_costobject_name',
                      'title'  => _("Cost Object")),
                array('name'   => 'hours',
                      'title'  => _("Hours"),
                      'type'   => 'number',
                      'align'  => 'right'));
            if ($GLOBALS['conf']['time']['choose_ifbillable']) {
                $columns[] =
                    array('name'   => 'billable',
                          'title'  => _("Bill?"),
                          'type'   => 'enum',
                          'params' => array($yesno));
            }
            $columns = array_merge($columns, array(
                array('name'  => 'description',
                      'title' => _("Description")),
                array('name'  => 'note',
                      'title' => _("Notes"))));

            $colspan = 6;
            if ($GLOBALS['conf']['time']['choose_ifbillable']) {
                $colspan++;
            }
            $fColumns = array(
                array('name'    => 'approval',
                      'colspan' => $colspan,
                      'type'    => '%html',
                      'align'   => 'right'),
                array('name'    => 'hours',
                      'type'    => 'number',
                      'align'   => 'right'));
            if ($GLOBALS['conf']['time']['choose_ifbillable']) {
                $fColumns[] =
                    array('name'   => 'billable',
                          'type'   => 'enum',
                          'params' => array($yesno));
            }
            $fColumns = array_merge($fColumns, array(
                array('name' => 'description'),
                array('name' => 'blank2')));

            return array('title' => _("Search Results"),
                         'sections' => array(
                             'data' => array(
                                 'rows' => count($hours),
                                 'columns' => $columns),
                             'footer' => array(
                                 'rows' => 3,
                                 'strong' => true,
                                 'columns' => $fColumns)));

        default:
            throw new Hermes_Exception(sprintf(_("\"%s\" is not a defined table."), $name));
        }
    }

    /**
     * @TODO
     *
     * @param <type> $name
     * @param <type> $params
     * @return string
     */
    public static function getTableData($name, $params)
    {
        switch ($name) {
        case 'hours':
            $time_data = $GLOBALS['injector']->getInstance('Hermes_Driver')->getHours($params);
            $subtotal_column = null;
            if ($search_mode = $GLOBALS['session']->get('hermes', 'search_mode')) {
                switch ($search_mode) {
                case 'date':
                    $subtotal_column = 'date';
                    break;

                case 'employee':
                    $subtotal_column = 'employee';
                    break;

                case 'client':
                    $subtotal_column = '_client_name';
                    break;

                case 'jobtype':
                    $subtotal_column = '_type_name';
                    break;

                case 'costobject':
                    $subtotal_column = '_costobject_name';
                    break;
                }

                $clients = Hermes::listClients();
                $column = array();
                foreach ($time_data as $key => $row) {
                    if (empty($row['client'])) {
                        $time_data[$key]['_client_name'] = _("no client");
                    } elseif (isset($clients[$row['client']])) {
                        $time_data[$key]['_client_name'] = $clients[$row['client']];
                    } else {
                        $time_data[$key]['_client_name'] = $row['client'];
                    }
                    if (!is_null($subtotal_column)) {
                      $column[$key] = $time_data[$key][$subtotal_column];
                    }
                }
                if (!is_null($subtotal_column)) {
                    array_multisort($column, SORT_ASC, $time_data);
                }
            }

            $total_hours = 0.0;
            $total_billable_hours = 0.0;
            $subtotal_hours = 0.0;
            $subtotal_billable_hours = 0.0;
            $subtotal_control = null;

            $result['data'] = array();
            foreach ($time_data as $k => $vals) {
                // Initialize subtotal break value.
                if (is_null($subtotal_control) && isset($vals[$subtotal_column])) {
                    $subtotal_control = $vals[$subtotal_column];
                }

                if (!empty($subtotal_column) &&
                    $vals[$subtotal_column] != $subtotal_control) {
                    Hermes_Api::renderSubtotals($result['data'], $subtotal_hours, $subtotal_billable_hours,
                                    $subtotal_column == 'date' ? strftime("%m/%d/%Y", $subtotal_control) :
                                    $subtotal_control);
                    $subtotal_hours = 0.0;
                    $subtotal_billable_hours = 0.0;
                    $subtotal_control = $vals[$subtotal_column];
                }

                // Set up edit/delete icons.
                if (Hermes::canEditTimeslice($vals['id'])) {
                    $edit_link = Horde::url('entry.php', true)
                      ->add(array(
                        'id' => $vals['id'],
                        'url' => Horde::selfUrl(true, true, true)));
                    $vals['icons'] =
                        Horde::link($edit_link, _("Edit Entry")) .
                        Horde::img('edit.png', _("Edit Entry"), '') . '</a>';

                    if (empty($vals['submitted'])) {
                        $vals['checkbox'] =
                            '<input type="checkbox" name="item[' .
                            htmlspecialchars($vals['id']) .
                            ']" checked="checked" />';
                    } else {
                        $vals['checkbox'] = '';
                    }
                }

                // Add to totals.
                $subtotal_hours += (double)$vals['hours'];
                $total_hours += (double)$vals['hours'];
                if ($vals['billable']) {
                    $subtotal_billable_hours += (double)$vals['hours'];
                    $total_billable_hours += (double)$vals['hours'];
                }

                // Localize hours.
                $vals['hours'] = sprintf('%.02f', $vals['hours']);

                $result['data'][] = $vals;
            }

            if (!empty($subtotal_column)) {
                Hermes_Api::renderSubtotals($result['data'], $subtotal_hours, $subtotal_billable_hours,
                                $subtotal_column == 'date' ? strftime("%m/%d/%Y", $subtotal_control) :
                                $subtotal_control);
            }

            // Avoid a divide by zero.
            if ($total_hours == 0.0) {
                $billable_pct = 0.0;
            } else {
                $billable_pct = round($total_billable_hours / $total_hours * 100.0);
            }

            $descr = _("Billable Hours") . ' (' . $billable_pct . '%)';
            $result['footer'] = array();
            $result['footer'][] = array(
                'hours'       => sprintf('%.02f', $total_billable_hours),
                'description' => $descr);

            $descr = _("Non-billable Hours") . ' (' . (100.0 - $billable_pct) . '%)';
            $result['footer'][] = array(
                'hours'       => sprintf('%.02f', $total_hours - $total_billable_hours),
                'description' => $descr);
            $result['footer'][] = array(
                'hours'       => sprintf('%.02f', $total_hours),
                'description' => _("Total Hours"),
                'approval'    => '<div id="approval">' . _("Approved By:") .
                                 ' ________________________________________ ' .
                                 '&nbsp;</div>');
            break;
        }

        return $result;
    }

    /**
     * @TODO
     *
     * @param <type> $table_data
     * @param <type> $hours
     * @param <type> $billable_hours
     * @param <type> $value
     * @return <type>
     */
    public static function renderSubtotals(&$table_data, $hours, $billable_hours, $value)
    {
        $billable_pct = ($hours == 0.0) ? 0.0 :
             round($billable_hours / $hours * 100.0);
        $descr = _("Billable Hours") . ' (' . $billable_pct . '%)';
        $table_data[] = array(
            'date'             => '',
            'employee'         => '',
            'client'           => '',
            'billable'         => '',
            'note'             => '',
            '_type_name'       => '',
            '_costobject_name' => '',
            'hours'            => sprintf('%.02f', $billable_hours),
            'description'      => $descr);
         $descr = _("Non-billable Hours") . ' (' . (100.0 - $billable_pct) . '%)';
         $table_data[] = array(
            'hours'       => sprintf('%.02f', $hours - $billable_hours),
            'description' => $descr);
         $table_data[] = array(
            'hours'       => sprintf('%.02f', $hours),
            'description' => sprintf(_("Total Hours for %s"), $value),
             );

        return;
    }

    /**
     * listCostObjects API
     *
     * @param array $criteria  The search criteria
     *
     * @return array  A listCostObjects result array.
     */
    public function listCostObjects($criteria)
    {
        if (!$GLOBALS['conf']['time']['deliverables']) {
            return array();
        }

        $deliverables = $GLOBALS['injector']->getInstance('Hermes_Driver')->listDeliverables($criteria);
        if (empty($criteria['id'])) {
            /* Build heirarchical tree. */
            $levels = array();
            $hash = array();
            foreach ($deliverables as $deliverable) {
                if (empty($deliverable['parent'])) {
                    $parent = -1;
                } else {
                    $parent = $deliverable['parent'];
                }
                $levels[$parent][$deliverable['id']] = $deliverable['name'];
                $hash[$deliverable['id']] = $deliverable;
            }

            /* Sort levels alphabetically, keeping keys intact. */
            foreach ($levels as $key => $level) {
                asort($levels[$key]);
            }

            /* Traverse the tree and glue them back together. Lots of magic
             * involved, so don't try to understand. */
            $elts = array();
            $stack = empty($levels[-1]) ? array() : array(-1);
            while (count($stack)) {
                if (!(list($key, $val) = each($levels[$stack[count($stack) - 1]]))) {
                    array_pop($stack);
                    continue;
                }
                $elts[$key] = str_repeat(' + ', count($stack)-1) . $val;
                if (!empty($levels[$key])) {
                    $stack[] = $key;
                }
            }

            $results = array();
            foreach ($elts as $key => $value) {
                $results[] = array('id'       => $key,
                                   'active'   => $hash[$key]['active'],
                                   'estimate' => $hash[$key]['estimate'],
                                   'name'     => $value);
            }
        } else {
            $results = $deliverables;
        }

        if (!$results) {
            return array();
        }

        return array(array('category' => _("Deliverables"),
                           'objects'  => $results));
    }

    /**
     * Retrieve list of job types.
     *
     * @param array $criteria  Hash of filter criteria:
     *
     *                      'enabled' => If present, only retrieve enabled
     *                                   or disabled job types.
     *
     * @return array Associative array of job types
     */
    public function listJobTypes($criteria = array())
    {
        return $GLOBALS['injector']->getInstance('Hermes_Driver')->listJobTypes($criteria);
    }

    /**
     *
     * @see Hermes::listClients
     */
    public function listClients()
    {
        return Hermes::listClients();
    }

    /**
     * Record a time slice
     *
     * @param array $data  Slice attributes
     *<pre>
     *  date          - The slice date (required).
     *  client        - The client id (required).
     *  type          - The jobType id (required).
     *  costobject    - The costObject id [none]
     *  hours         - Number of hours (required).
     *  billable      - Time billable? [true]
     *  description   - Description (required)
     *  note          - Note [blank]
     *</pre>
     *
     * @return <type>
     */
    public function recordTime(array $data)
    {
        $data = new Horde_Support_Array($data);
        // Check for required
        if (!$data->date || !$data->client || !$data->type || !$data->hours || !$data->description) {
            throw new Hermes_Exception(_("Missing required values: check data and retry"));
        }

        if ($data->employee) {
            if (!$GLOBALS['registry']->isAdmin(array('permission' => 'hermes:review', 'permlevel' => Horde_Perms::EDIT))) {
              throw new Hermes_Exception(_("Only time reviewers with edit permissions can post time for other users."));
            }
        } else {
            $data->employee = $GLOBALS['registry']->getAuth();
        }

        // Parse date
        try {
            // Check for stdObject types
            $dateobj = new Horde_Date($data->date->scalar);
        } catch (Exception $e) {
            $dateobj = new Horde_Date($data->date);
        }
        $date['year'] = $dateobj->year;
        $date['month'] = $dateobj->month;
        $date['day'] = $dateobj->mday;
        $data->date = $date;

        if (!$data->billable) {
            $data->billable = true;
        }

        return $GLOBALS['injector']->getInstance('Hermes_Driver')->enterTime($data->employee, $data);
    }

    /**
     * Retrieve information about a costobject's hours. Includes number of hours
     * worked on for each employee, total billed time etc...
     *
     * @param string $costobject  The costobject id (e.g., "whups:15").
     *
     * @return array  An array of data with the following structure:
     *</pre>
     *  employees  - an array of employee ids as keys, number of hours as values.
     *  total      - total number of hours
     *  billable   - total number of billable hours.
     *</pre>
     */
    public function getCostObjectInfo($costobject)
    {
        $filter = array('costobject' => $costobject);
        $slices = $GLOBALS['injector']->getInstance('Hermes_Driver')->getHours($filter);
        $billable = $time = 0;
        $employees = array();
        foreach ($slices as $slice) {
            $time += $slice['hours'];
            if ($slice['billable']) {
              $billable += $slice['hours'];
            }

            $employees[$slice['employee']] += $slice['hours'];
        }

        return array('employees' => $employees,
                    'total' => $time,
                    'billable' => $billable);

    }

}
