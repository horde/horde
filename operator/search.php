<?php
/**
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$operator = Horde_Registry::appInit('operator');

require_once OPERATOR_BASE . '/lib/Form/SearchCDR.php';

$cache = $GLOBALS['cache'];
$renderer = new Horde_Form_Renderer();
$vars = Horde_Variables::getDefaultVariables();
$data = array();

if (!$vars->exists('rowstart')) {
    $rowstart = 0;
} elseif (!is_numeric($rowstart = $vars->get('rowstart'))) {
    $notification->push(_("Invalid number for row start.  Using 0."));
    $rowstart = 0;
}

if (isset($_SESSION['operator']['lastdata'])) {
    $data = $_SESSION['operator']['lastdata'];
}

$form = new SearchCDRForm(_("Search Call Detail Records"), $vars);
if ($form->isSubmitted() && $form->validate($vars, true)) {
    $accountcode = $vars->get('accountcode');
    $dcontext = $vars->get('dcontext');
    if (empty($dcontext)) {
        $dcontext = '%';
    }

    try {
        $start = new Horde_Date($vars->get('startdate'));
        $end = new Horde_Date($vars->get('enddate'));
        list($stats, $data) = $operator->driver->getRecords($start, $end,
                                                            $accountcode,
                                                            $dcontext, $rowstart,
                                                            $GLOBALS['conf']['storage']['searchlimit']);

        $_SESSION['operator']['lastsearch']['params'] = array(
            'accountcode' => $vars->get('accountcode'),
            'dcontext' => $vars->get('dcontext'),
            'startdate' => $vars->get('startdate'),
            'enddate' => $vars->get('enddate'));
        $_SESSION['operator']['lastdata'] = $data;

    } catch (Exception $e) {
        //$notification->push(_("Invalid date requested."));
        $notification->push($e);
        $data = array();
    }
} else {
    if (isset($_SESSION['operator']['lastsearch']['params'])) {
        foreach($_SESSION['operator']['lastsearch']['params'] as $var => $val) {
            $vars->set($var, $val);
        }
    }
}

// Create the Pager UI
$page = Horde_Util::getGet('page', 0);
$pager_vars = Horde_Variables::getDefaultVariables();
$pager_vars->set('page', $page);
$perpage = $prefs->getValue('rowsperpage');
$pager = new Horde_Core_Ui_Pager('page', $pager_vars,
                            array('num' => count($data),
                                  'url' => 'search.php',
                                  'page_count' => 10,
                                  'perpage' => $perpage));

// Limit the domain list to the current page
$data = array_slice($data, $page*$perpage, $perpage);

// See if we got the complete set of records
if ($stats['numcalls'] > $GLOBALS['conf']['storage']['searchlimit']) {
    $msg = _("Number of calls exceeded search limit (%s).  Try narrowing your search dates or exporting the data.");
    $notification->push(sprintf($msg, $GLOBALS['conf']['storage']['searchlimit']), 'horde.warning');
}

$title = _("Search Call Detail Records");
Horde::addScriptFile('stripe.js', 'horde', true);

require OPERATOR_TEMPLATES . '/common-header.inc';
require OPERATOR_TEMPLATES . '/menu.inc';
$notification->notify();
$form->renderActive($renderer, $vars, Horde::url('search.php'), 'post');

$columns = unserialize($prefs->getValue('columns'));
if (!empty($data)) {
    require OPERATOR_TEMPLATES . '/search.inc';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
