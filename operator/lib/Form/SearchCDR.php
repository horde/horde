<?php
/**
 * SearchCDRForm Class
 *
 * Copyright 2008 Alkaloid Networks LLC <http://projects.alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Operator
 */

class SearchCDRForm extends Horde_Form {

    public function __construct($title, &$vars)
    {
        parent::__construct($vars, $title);

        // FIXME: Generate a list of clients from Turba?
        //$clients =

        $now = time();
        if (!$vars->exists('startdate')) {
            // Default to the beginning of the previous calendar month
            $startdate = array('day' => 1,
                               'month' => date('n', $now),
                               'year' => date('Y', $now),
                               'hour' => 0,
                               'minute' => 0,
                               'second' => 0);
            $vars->set('startdate', $startdate);
        }

        if (!$vars->exists('enddate')) {
            // Default to the end of the previous calendar month
            $month = date('n', $now);
            $year = date('Y', $now);
            $lastday = Horde_Date_Utils::daysInMonth($month, $year);
            $enddate = array('day' => $lastday,
                             'month' => $month,
                             'year' => $year,
                             'hour' => 23,
                             'minute' => 59,
                             'second' => 59);
            $vars->set('enddate', $enddate);
        }

        try {
            $accountcodes = Operator::getAccountCodes(true);
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e);
            $accountcodes = array();
        }


        // Parameters for Horde_Form_datetime
        $start_year = date('Y', $now) - 5;
        $end_year = '';
        $picker = true;
        $format_in = null;
        $format_out = '%x';
        $show_seconds = true;
        $params = array($start_year, $end_year, $picker, $format_in,
                        $format_out, $show_seconds);

        $this->addVariable(_("Account Code"), 'accountcode', 'enum', false,
                           false, null, array($accountcodes));
        $this->addVariable(_("Destination Context"), 'dcontext', 'text', false,
                           false, _("An empty destination context will match all destination contexts."));
        $this->addVariable(_("Start Date & Time"), 'startdate', 'datetime',
                           true, false, null, $params);
        $this->addVariable(_("End Date & Time"), 'enddate', 'datetime', true,
                           false, null, $params);
    }
}

class GraphCDRForm extends SearchCDRForm
{
    public function __construct($title, &$vars)
    {
        parent::__construct($title, $vars);

        $graphtypes = Operator::getGraphInfo();
        $graphs = array();
        foreach ($graphtypes as $type => $info) {
            $graphs[$type] = $info['title'];
        }

        $this->addVariable(_("Graph"), 'graph', 'enum', true, false,
                           null, array($graphs));
    }
}

class ExportCDRForm extends SearchCDRForm
{
    public function __construct($title, &$vars)
    {
        parent::__construct($title, $vars);

        $formats = array(
            Horde_Data::EXPORT_CSV => 'Comma-Delimited (CSV)',
            Horde_Data::EXPORT_TSV => 'Tab-Delimited',
        );

        $this->addVariable(_("Data Format"), 'format', 'enum', true, false,
                           null, array($formats));
    }

    public function execute()
    {
        global $operator;

        $start = new Horde_Date($this->_vars->get('startdate'));
        $end = new Horde_Date($this->_vars->get('enddate'));
        $accountcode = $this->_vars->get('accountcode');
        $dcontects = $this->_vars->get('dcontext');
        if (empty($dcontext)) {
            $dcontext = '%';
        }
        list($stats, $data) = $operator->driver->getRecords($start, $end,
                                                            $accountcode,
                                                            $dcontext, 0,
                                                            null);
        switch($this->_vars->get('format')) {
        case Horde_Data::EXPORT_CSV:
            $ext = 'csv';
            $fmt = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Data')->create('Csv');
            break;

        case Horde_Data::EXPORT_TSV:
            $ext = 'tsv';
            $fmt = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Data')->create('Tsv');
            break;

        default:
            throw new Operator_Exception(_("Invalid data format requested."));
            break;
        }

        $filename = 'export-' . uniqid() . '.' . $ext;
        $fmt->exportFile($filename, $data, true);
        exit;
    }
}
