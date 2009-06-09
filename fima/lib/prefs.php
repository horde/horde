<?php
/**
 * $Horde: fima/lib/prefs.php,v 1.0 2008/09/23 20:12:08 trt Exp $
 *
 * Copyright 2002-2006 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */

function handle_ledgerselect($updated)
{
    global $prefs;

    $active_ledger = Horde_Util::getFormData('active_ledger');
    if (!is_null($active_ledger)) {
        $ledgers = Fima::listLedgers();
        if (is_array($ledgers) && array_key_exists($active_ledger, $ledgers)) {
            $prefs->setValue('active_ledger', $active_ledger);
            $updated = true;
        }
    }
    return $updated;
}

function handle_closedperiodselect($updated)
{
    global $prefs;
    
    $period = Horde_Util::getFormData('closedperiod');
    if ((int)$period['year'] > 0 && (int)$period['month'] > 0) {
        $period = mktime(0, 0, 0, $period['month'] + 1, 0, $period['year']);
    } else {
        $period = 0;
    }
    $prefs->setValue('closed_period', $period);
    $updated = true;
    
    return $updated;
}
