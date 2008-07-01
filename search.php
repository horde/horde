<?php
/**
 * $Horde: incubator/operator/search.php,v 1.3 2008/07/01 22:25:00 bklang Exp $
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
    $data = $operator_driver->getData($start, $end, $accountcode, $dcontext);
    $_SESSION['operator']['lastsearch']['params'] = array(
        'accountcode' => $vars->get('accountcode'),
        'dcontext' => $vars->get('dcontext'),
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

$title = _("Search Call Detail Records");
Horde::addScriptFile('stripe.js', 'horde', true);

require OPERATOR_TEMPLATES . '/common-header.inc';
require OPERATOR_TEMPLATES . '/menu.inc';

$form->renderActive($renderer, $vars);

$columns = unserialize($prefs->getValue('columns'));
if (!empty($data)) {
    require OPERATOR_TEMPLATES . '/search/header.inc';
    foreach ($data as $record) {
        require OPERATOR_TEMPLATES . '/search/row.inc';
    }
    require OPERATOR_TEMPLATES . '/search/footer.inc';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
