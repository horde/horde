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

$form = new ExportCDRForm(_("Export Call Detail Records"), $vars);
if ($form->isSubmitted() && $form->validate($vars, true)) {
    try {
        $_SESSION['operator']['lastsearch']['params'] = array(
            'accountcode' => $vars->get('accountcode'),
            'dcontext' => $vars->get('dcontext'),
            'startdate' => $vars->get('startdate'),
            'enddate' => $vars->get('enddate'));
        $_SESSION['operator']['lastdata'] = $data;

        $form->execute();

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

$title = _("Export Call Detail Records");

require OPERATOR_TEMPLATES . '/common-header.inc';
require OPERATOR_TEMPLATES . '/menu.inc';
$notification->notify();
$form->renderActive($renderer, $vars, Horde::url('export.php'), 'post');;


require $registry->get('templates', 'horde') . '/common-footer.inc';
