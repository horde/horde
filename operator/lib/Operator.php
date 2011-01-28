<?php
/**
 * Operator Base Class.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Operator
 */
class Operator {

    /**
     * Build Operator's list of menu items.
     */
    public static function getMenu($returnType = 'object')
    {
        global $conf, $registry, $browser, $print_link;

        $menu = new Horde_Menu(Horde_Menu::MASK_ALL);
        $menu->add(Horde::url('viewgraph.php'), _("_View Graphs"), 'graphs.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        $menu->add(Horde::url('search.php'), _("_Search"), 'search.png');

        /* Export */
        if ($GLOBALS['conf']['menu']['export']) {
            $menu->add(Horde::url('export.php'), _("_Export"), 'data.png');
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

    public static function getColumns()
    {
        static $columns = array();
        if (!empty($columns)) {
            return $columns;
        }

        $columns = array(
            'accountcode' => _("Account Code"),
            'src' => _("Source"),
            'dst' => _("Destination"),
            'dcontext' => _("Destination Context"),
            'clid' => _("Caller ID"),
            'channel' => _("Channel"),
            'dstchannel' => _("Destination Channel"),
            'lastapp' => _("Last Application"),
            'lastdata' => _("Last Application Data"),
            'start' => _("Start Time"),
            'answer' => _("Answer Time"),
            'end' => _("End Time"),
            'duration' => _("Duration (sec)"),
            'billsec' => _("Billable Duration (sec)"),
            'disposition' => _("Disposition"),
            'amaflags' => _("AMA Flag"),
            'userfield' => _("User Defined Field"),
            'uniqueid' => _("Unique ID"));

        return $columns;
    }


    public static function getColumnName($column)
    {
        $columns = Operator::getColumns();
        return $columns[$column];
    }

    public static function getAMAFlagName($flagid)
    {
        // See <asterisk/cdr.h> for definitions
        switch($flagid) {
        case 1:
            return _("OMIT");
            break;
        case 2:
            return _("BILLING");
            break;
        case 3:
            return _("DOCUMENTATION");
            break;
        }
    }

    /**
     * Get a list of valid account codes from the database
     *
     * @return array  List of valid account codes.
     */
    public static function getAccountCodes($permfilter = false)
    {
        $operator = $GLOBALS['registry']->getApiInstance('operator', 'application');;

        // Set up arrays for filtering
        $keys = $values = $operator->driver->getAccountCodes();
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

        if ($GLOBALS['registry']->isAdmin() ||
            $perms->hasPermission('operator:accountcodes',
                                             $GLOBALS['registry']->getAuth(),
                                             Horde_Perms::READ)) {
            $permfilter = false;
        }

        if (!$permfilter ||
            $perms->hasPermission('operator:accountcodes:%',
                                             $GLOBALS['registry']->getAuth(),
                                             Horde_Perms::READ)) {

            // Add an option to select all accounts
            array_unshift($keys, '%');
            array_unshift($values, _("-- All Accounts Combined --"));
        }

        // Only add the Empty value if it is exists in the backend
        if ($index = array_search('', $values)) {
           $values[$index] = _("-- Empty Accountcode --");
        }

        if ($permfilter) {
            // Filter the returned list of account codes through Permissions
            // if requested.
            $accountcodes = array();
            foreach ($keys as $index => $accountcode) {
                if (empty($accountcode)) {
                    $permitem = 'operator:accountcodes';
                } else {
                    $permitem = 'operator:accountcodes:' . $accountcode;
                }

                if ($GLOBALS['registry']->isAdmin() ||
                    $perms->hasPermission($permitem, $GLOBALS['registry']->getAuth(), Horde_Perms::SHOW)) {
                    $accountcodes[$accountcode] = $values[$index];
                }
            }

            if (empty($accountcodes)) {
                throw new Operator_Exception(_("You do not have permission to view any accounts."));
            }
        } else {
            $accountcodes = array_combine($keys, $values);
        }
        return $accountcodes;
    }

    public static function getGraphInfo($graphid = null)
    {
        static $graphs;

        if (empty($graphs)) {
            $graphs = array(
                'numcalls' => array(
                    'title' => _("Number of Calls by Month"),
                    'axisX' => _("Month"),
                    'axisY' => _("Number of Calls"),
                ),
                'minutes' => array(
                    'title' => _("Total Minutes Used by Month"),
                    'axisX' => _("Month"),
                    'axisY' => _("Minute"),
                    'numberformat' => '%0.1f',
                ),

//                'failed' => array(
//                    'title' => _("Number of Failed Calls by Month"),
//                    'axisX' => _("Month"),
//                    'axisY' => _("Failed Calls"),
//                ),
             );
        }

        if ($graphid === null) {
            return $graphs;
        }

        if (isset($graphs[$graphid])) {
            return $graphs[$graphid];
        } else {
            throw new Operator_Exception(_("Invalid graph type."));
        }
    }

}
