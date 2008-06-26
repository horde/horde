<?php
/**
 * $Horde: incubator/operator/viewgraph.php,v 1.1 2008/06/26 17:31:47 bklang Exp $
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
    if ($vars->exists('accountcode')) {
        $accountcode = $vars->get('accountcode');
    } else {
        $accountcode = '';
    }
    $start = new Horde_Date($vars->get('startdate'));
    $end = new Horde_Date($vars->get('enddate'));
    $data = $operator_driver->getData($accountcode, $start, $end);
    $_SESSION['operator']['lastsearch']['params'] = array(
        'accountcode' => $vars->get('accountcode'),
        'startdate' => $vars->get('startdate'),
        'enddate' => $vars->get('enddate'));
    $_SESSION['operator']['lastsearch']['data'] = $data;
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

$startdate = array('year' => 2007,
                   'month' => 1,
                   'mday' => 1);
$enddate = array('year' => date('Y'),
                 'month' => date('n'),
                 'mday' => date('j'));

$startdate = new Horde_Date($startdate);
$enddate = new Horde_Date($enddate);
$accountcode = null;
$dcontext = null;

$stats = $operator_driver->getCallStats($startdate, $enddate, $accountcode, $dcontext);

$title = _("Call Statistics");
Horde::addScriptFile('stripe.js', 'horde', true);

require OPERATOR_TEMPLATES . '/common-header.inc';
require OPERATOR_TEMPLATES . '/menu.inc';

print_r($stats);
require $registry->get('templates', 'horde') . '/common-footer.inc';
