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

$form = new GraphCDRForm(_("Graph CDR Data"), $vars);
if ($form->isSubmitted() && $form->validate($vars, true)) {
    $accountcode = $vars->get('accountcode');
    $dcontext = $vars->get('dcontext');
    if (empty($dcontext)) {
        $dcontext = '%';
    }

    try {
        $start = new Horde_Date($vars->get('startdate'));
        $end = new Horde_Date($vars->get('enddate'));

        if (($end->month - $start->month) == 0 &&
            ($end->year - $start->year) == 0) {
            // FIXME: This should not cause an error but is due to a bug in
            // Image_Graph.
            $notification->push(_("You must select a range that includes more than one month to view these graphs."), 'horde.warning');
        } else {
            // See if we have cached data
            $cachekey = md5(serialize(array('getMonthlyCallStats', $start, $end,
                                            $accountcode, $dcontext)));
            // Use 0 lifetime to allow cache lifetime to be set when storing
            // the object.
            $stats = $cache->get($cachekey, 0);
            if ($stats === false) {
                $stats = $operator->driver->getMonthlyCallStats($start,
                                                               $end,
                                                               $accountcode,
                                                               $dcontext);

                $res = $cache->set($cachekey, serialize($stats), 600);
                if ($res === false) {
                    Horde::logMessage('The cache system has experienced an error.  Unable to continue.', 'ERR');
                    $notification->push(_("Internal error.  Details have been logged for the administrator."));
                    $stats = array();
                }

            } else {
                // Cached data is stored serialized
                $stats = unserialize($stats);
            }
            $session->set('operator', 'lastsearch/params', array(
                'accountcode' => $vars->get('accountcode'),
                'dcontext' => $vars->get('dcontext'),
                'startdate' => $vars->get('startdate'),
                'enddate' => $vars->get('enddate')
            ));
        }
    } catch (Horde_Exception $e) {
        //$notification->push(_("Invalid dates requested."));
        $notification->push($e);
        $stats = array();
    }
} else {
    foreach ($session->get('operator', 'lastsearch/params', Horde_Session::TYPE_ARRAY) as $var => $val) {
        $vars->set($var, $val);
    }
    $data = $session->get('operator', 'lastsearch/data', Horde_Session::TYPE_ARRAY);
}

$graphs = array();
if (!empty($stats)) {
    $url = Horde::url('graphgen.php');
    $graphtypes = Operator::getGraphInfo();

    foreach($graphtypes as $type => $info) {
        $graphs[$type] = Horde_Util::addParameter($url, array(
                            'graph' => $type, 'key' => $cachekey));
    }
}
$curgraph = $vars->get('graph');

$title = _("Call Detail Records Graph");

require OPERATOR_TEMPLATES . '/common-header.inc';
require OPERATOR_TEMPLATES . '/menu.inc';

$form->renderActive($renderer, $vars, Horde::url('viewgraph.php'), 'post');

if (!empty($stats) && !empty($graphs[$curgraph])) {
    echo '<br />';
    echo '<img src="' . $graphs[$curgraph] . '"/><br />';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';

// Don't leave stale stats lying about
$session->remove('operator', 'stats');
