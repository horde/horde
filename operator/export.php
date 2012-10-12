<?php
/**
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

require_once __DIR__ . '/lib/Application.php';
$operator = Horde_Registry::appInit('operator');

require_once OPERATOR_BASE . '/lib/Form/SearchCDR.php';

$cache = $GLOBALS['cache'];
$renderer = new Horde_Form_Renderer();
$vars = Horde_Variables::getDefaultVariables();

if (!$vars->exists('rowstart')) {
    $rowstart = 0;
} elseif (!is_numeric($rowstart = $vars->get('rowstart'))) {
    $notification->push(_("Invalid number for row start.  Using 0."));
    $rowstart = 0;
}

$data = $session->get('operator', 'lastdata', Horde_Session::TYPE_ARRAY);

$form = new ExportCDRForm(_("Export Call Detail Records"), $vars);
if ($form->isSubmitted() && $form->validate($vars, true)) {
    try {
        $session->set('operator', 'lastsearch/params', array(
            'accountcode' => $vars->get('accountcode'),
            'dcontext' => $vars->get('dcontext'),
            'startdate' => $vars->get('startdate'),
            'enddate' => $vars->get('enddate')
        ));
        $session->set('operator', 'lastdata', $data);

        $form->execute();

    } catch (Exception $e) {
        //$notification->push(_("Invalid date requested."));
        $notification->push($e);
        $data = array();
    }
} else {
    foreach($session->get('operator', 'lastsearch/params', Horde_Session::TYPE_ARRAY) as $var => $val) {
        $vars->set($var, $val);
    }
}

$page_output->header(array(
    'title' => _("Export Call Detail Records")
));
$notification->notify(array('listeners' => 'status'));
$notification->notify();
$form->renderActive($renderer, $vars, Horde::url('export.php'), 'post');
$page_output->footer();
