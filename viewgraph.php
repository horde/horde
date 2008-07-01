<?php
/**
 * $Horde: incubator/operator/viewgraph.php,v 1.4 2008/07/01 22:25:00 bklang Exp $
 *
 * Copyright 2008 Alkaloid Networks LLC <http://projects.alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

@define('OPERATOR_BASE', dirname(__FILE__));
require_once OPERATOR_BASE . '/lib/base.php';

// Form libraries.
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';
require_once OPERATOR_BASE . '/lib/Form/SearchCDR.php';

$renderer = new Horde_Form_Renderer();
$vars = Variables::getDefaultVariables();

$form = new SearchCDRForm($vars);
if ($form->isSubmitted() && $form->validate($vars, true)) {
    $accountcode = $vars->get('accountcode');
    $dcontext = $vars->get('dcontext');
    $start = new Horde_Date($vars->get('startdate'));
    $end = new Horde_Date($vars->get('enddate'));

    // See if we have cached data
    $cachekey = md5(serialize(array('getMonthlyCallStats', $start, $end,
                                    $accountcode, $dcontext)));
    // Use 0 lifetime to allow cache lifetime to be set when storing the object
    $stats = $cache->get($cachekey, 0);
    if ($stats === false) {
        $stats = $operator_driver->getMonthlyCallStats($start, $end,
                                                       $accountcode, $dcontext);
        $res = $cache->set($cachekey, serialize($stats), 600);
        if ($res === false) {
            Horde::logMessage('The cache system has experienced an error.  Unable to continue.', __FILE__, __LINE__, PEAR_LOG_ERR);
            $notification->push(_("Internal error.  Details have been logged for the administrator."));
            unset($stats);
        }
    } else {
        // Cached data is stored serialized
        $stats = unserialize($stats);
    }
    $_SESSION['operator']['lastsearch']['params'] = array(
        'accountcode' => $vars->get('accountcode'),
        'dcontext' => $vars->get('dcontext'),
        'startdate' => $vars->get('startdate'),
        'enddate' => $vars->get('enddate'));
} else {
    if (isset($_SESSION['operator']['lastsearch']['params'])) {
        foreach($_SESSION['operator']['lastsearch']['params'] as $var => $val) {
            $vars->set($var, $val);
        }
    }
    if (isset($_SESSION['operator']['lastsearch']['data'])) {
        $data = $_SESSION['operator']['lastsearch']['data'];
    }
}

if (!empty($stats)) {
    $numcalls_graph = $minutes_graph = $failed_graph =
                      Horde::applicationUrl('graphgen.php');
    
    $numcalls_graph = Util::addParameter($numcalls_graph, array(
        'graph' => 'numcalls', 'key' => $cachekey));
    $minutes_graph = Util::addParameter($minutes_graph, array(
        'graph' => 'minutes', 'key' => $cachekey));
    $failed_graph = Util::addParameter($failed_graph, array(
        'graph' => 'failed', 'key' => $cachekey));
}


$title = _("Call Detail Records Graph");

require OPERATOR_TEMPLATES . '/common-header.inc';
require OPERATOR_TEMPLATES . '/menu.inc';

$form->renderActive($renderer, $vars);

if (!empty($stats)) {
    echo '<br />';
    echo '<img src="' . $numcalls_graph . '"/><br />';
    echo '<img src="' . $minutes_graph . '"/><br />';
    echo '<img src="' . $failed_graph . '"/><br />';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';

// Don't leave stale stats lying about
unset($_SESSION['operator']['stats']);
