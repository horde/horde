<?php
/**
 * SearchCDRForm Class
 *
 * $Horde: incubator/operator/lib/Form/SearchCDR.php,v 1.3 2008/07/01 22:25:01 bklang Exp $
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

    function SearchCDRForm(&$vars)
    {
        global $operator_driver;

        parent::Horde_Form($vars, _("Search CDR Data"));

        // FIXME: Generate a list of clients from Turba?
        //$clients = 

        $now = time();
        if (!$vars->exists('startdate')) {
            // Default to the beginning of the previous calendar month
            $startdate = array('day' => 1,
                               'month' => date('n', $now) - 1,
                               'year' => date('Y', $now),
                               'hour' => 0,
                               'minute' => 0,
                               'second' => 0);
            $vars->set('startdate', $startdate);
        }

        if (!$vars->exists('enddate')) {
            // Default to the end of the previous calendar month
            $month = date('n', $now) - 1;
            $year = date('Y', $now);
            $lastday = Horde_Date::daysInMonth($month, $year);
            $enddate = array('day' => $lastday,
                             'month' => $month,
                             'year' => $year,
                             'hour' => 23,
                             'minute' => 59,
                             'second' => 59);
            $vars->set('enddate', $enddate);
        }


        // Parameters for Horde_Form_datetime
        $start_year = date('Y', $now) - 3;
        $end_year = '';
        $picker = true;
        $format_in = null;
        $format_out = '%x';
        $show_seconds = true;
        $params = array($start_year, $end_year, $picker, $format_in,
                        $format_out, $show_seconds);

        $this->addVariable(_("Account Code"), 'accountcode', 'enum', false, false, null, array(Operator::getAccountCodes()));
        //$this->addVariable(_("Account Code"), 'accountcode', 'text', false, false, _("An empty account code will select records with an empty account code.  To search for all records account codes use %"));
        $this->addVariable(_("Destination Context"), 'dcontext', 'text', false, false, _("An empty destination context will select records with an empty destination context.  To search for all destination contexts use %"));
        $this->addVariable(_("Start Date/Time"), 'startdate', 'datetime', true, false, null, $params);
        $this->addVariable(_("End Date/Time"), 'enddate', 'datetime', true, false, null, $params);
    }
}
