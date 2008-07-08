<?php
/**
 * Operator Base Class.
 *
 * $Horde: incubator/operator/lib/Operator.php,v 1.12 2008/07/08 15:35:52 bklang Exp $
 *
 * Copyright 2008 The Horde Project <http://www.horde.org/>
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
    function getMenu($returnType = 'object')
    {
        global $conf, $registry, $browser, $print_link;

        require_once 'Horde/Menu.php';

        $menu = new Menu(HORDE_MENU_MASK_ALL);
        $menu->add(Horde::applicationUrl('viewgraph.php'), _("View Graphs"), 'graphs.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        $menu->add(Horde::applicationUrl('search.php'), _("Search"), 'search.png', $registry->getImageDir('horde'));

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

    function getColumns()
    {
        #static $columns = array(
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
            'start' => _("Call Start Time"),
            'answer' => _("Call Answer Time"),
            'end' => _("Call End Time"),
            'duration' => _("Call Duration (seconds)"),
            'billsec' => _("Billable Call Duration (seconds)"),
            'disposition' => _("Call Disposition"),
            'amaflags' => _("AMA Flag"),
            'userfield' => _("User Defined Field"),
            'uniqueid' => _("Call Unique ID"));

        return $columns;
    }


    function getColumnName($column)
    {
        $columns = Operator::getColumns();
        return $columns[$column];
    }

    function getAMAFlagName($flagid)
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
    function getAccountCodes($permfilter = false)
    {
        global $operator_driver;

        $accountcodes = $operator_driver->getAccountCodes();

        // Add an option to select all accounts
        $keys = $accountcodes;
        array_unshift($keys, '%');
        $values = $accountcodes;
        array_unshift($values, _("-- All Accounts --"));

        if ($index = array_search('', $values)) {
           $values[$index] = _("-- Empty Accountcode --");
        }

        // Make the index of each array entry the same as its value
        // array_combine() is PHP5-only
        //$accountcodes = array_combine($keys, $values);
        $accountcodes = array();

        // Filter the returned list of account codes through Permissions
        // if requested.
        foreach ($keys as $index => $accountcode) {
            if ($permfilter) {
                if (empty($accountcode) || $accountcode == '%') {
                    $permitem = 'operator:accountcodes';
                } else {
                    $permitem = 'operator:accountcodes:' . $accountcode;
                }

                if (Auth::isAdmin() ||
                    $GLOBALS['perms']->hasPermission($permitem,
                                                     Auth::getAuth(),
                                                     PERMS_SHOW)) {
                    $accountcodes[$accountcode] = $values[$index];
                }
            } else {
                $accountcodes[$accountcode] = $values[$index];
            }
        }
        return $accountcodes;
    }

    function getGraphInfo($graphid)
    {
        switch($graphid) {
        case 'numcalls':
            return array(
                'title' => _("Number of Calls by Month"),
                'axisX' => _("Month"),
                'axisY' => _("Number of Calls"),
            );
            break;
         case 'minutes':
            return array(
                'title' => _("Total Minutes Used by Month"),
                'axisX' => _("Month"),
                'axisY' => _("Minute"),
                'numberformat' => '%0.1f',
            );
            break;
         case 'failed':
            return array(
                'title' => _("Number of Failed Calls by Month"),
                'axisX' => _("Month"),
                'axisY' => _("Failed Calls"),
            );
            break;
         }
    }

}
