<?php
/**
 * Copyright 2008 Thomas Trethan <thomas@trethan.net>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('FIMA_BASE', dirname(__FILE__));
require_once FIMA_BASE . '/lib/base.php';
require_once FIMA_BASE . '/config/report.php';
require_once FIMA_BASE . '/lib/Report.php';

$actionID = Horde_Util::getFormData('actionID');

switch ($actionID) {
case 'open_report':
    $_SESSION['fima_report'] = array('report_id'       => Horde_Util::getFormData('report_id'),
                                     'display'		   => Horde_Util::getFormData('display'),
                                     'posting_account' => Horde_Util::getFormData('posting_account'),
                                     'period_start'    => Horde_Util::getFormData('period_start'),
                                     'period_end'      => Horde_Util::getFormData('period_end'),
                                     'reference_start' => Horde_Util::getFormData('reference_start'),
                                     'reference_end'   => Horde_Util::getFormData('reference_end'),
                                     'cumulate'        => Horde_Util::getFormData('cumulate'),
                                     'nullrows'        => Horde_Util::getFormData('nullrows'),
                                     'subaccounts'     => Horde_Util::getFormData('subaccounts'),
                                     'yearly'          => Horde_Util::getFormData('yearly'),
                                     'graph'           => Horde_Util::getFormData('graph'));
    break;
case 'clear_report':
    unset($_SESSION['fima_report']);
    break;
default:
    break;
}

/* Create params array. */
$params = isset($_SESSION['fima_report']) ? $_SESSION['fima_report'] : array();

/* Set initial values. */
if (!isset($params['report_id'])) {
    $params['report_id'] = '';
}
if (!isset($params['display'])) {
    $params['display'] = '';
}
if (!isset($params['posting_account'])) {
    $params['posting_account'] = array();
}
if (!isset($params['period_start'])) {
    $params['period_start'] = mktime(0, 0, 0, 1, 1);
} elseif (is_array($params['period_start'])) {
    $params['period_start'] = mktime(0, 0, 0, $params['period_start']['month'], 1, $params['period_start']['year']);
}
if (!isset($params['period_end'])) {
    $params['period_end'] = mktime(0, 0, 0, 12, 31);
} elseif (is_array($params['period_end'])) {
    $params['period_end'] = mktime(0, 0, 0, $params['period_end']['month'] + 1, 1, $params['period_end']['year']) - 1;
}
if (!isset($params['reference_start'])) {
    $params['reference_start'] = mktime(0, 0, 0, 1, 1, date('Y') - 1);
} elseif (is_array($params['reference_start'])) {
    $params['reference_start'] = mktime(0, 0, 0, $params['reference_start']['month'], 1, $params['reference_start']['year']);
}
if (!isset($params['reference_end'])) {
    $params['reference_end'] = mktime(0, 0, 0, 12, 31, date('Y') - 1);
} elseif (is_array($params['reference_end'])) {
    $params['reference_end'] = mktime(0, 0, 0, $params['reference_end']['month'] + 1, 1, $params['reference_end']['year']) - 1;
}
if (!isset($params['cumulate'])) {
    $params['cumulate'] = 0;
}
if (!isset($params['nullrows'])) {
    $params['nullrows'] = 0;
}
if (!isset($params['subaccounts'])) {
    $params['subaccounts'] = 0;
}
if (!isset($params['yearly'])) {
    $params['yearly'] = 0;
}
if (!isset($params['graph'])) {
    $params['graph'] = 0;
}

$params['out'] = Horde_Util::getFormData('out');
$params['sortby']  = Horde_Util::getFormData('sortby');
$params['sortdir'] = Horde_Util::getFormData('sortdir');

/* Get posting types and output displays. */
$types = Fima::getPostingTypes();
$displaylabel = _("%s [%s - %s - %s]");
$displays = array(FIMA_POSTINGTYPE_ACTUAL.'_'.FIMA_POSTINGTYPE_FORECAST.'_'.FIMA_POSTINGTYPE_BUDGET.'_reference' => sprintf($displaylabel, $types[FIMA_POSTINGTYPE_ACTUAL],   $types[FIMA_POSTINGTYPE_FORECAST], $types[FIMA_POSTINGTYPE_BUDGET], _("Reference")),
                  FIMA_POSTINGTYPE_ACTUAL.'_'.FIMA_POSTINGTYPE_FORECAST.'_difference_%'                          => sprintf($displaylabel, $types[FIMA_POSTINGTYPE_ACTUAL],   $types[FIMA_POSTINGTYPE_FORECAST], _("Difference"),                 _("%")),
                  FIMA_POSTINGTYPE_ACTUAL.'_'.FIMA_POSTINGTYPE_BUDGET.'_difference_%'                            => sprintf($displaylabel, $types[FIMA_POSTINGTYPE_ACTUAL],   $types[FIMA_POSTINGTYPE_BUDGET],   _("Difference"),                 _("%")),
                  FIMA_POSTINGTYPE_ACTUAL.'_reference_difference_%'                                              => sprintf($displaylabel, $types[FIMA_POSTINGTYPE_ACTUAL],   _("Reference"),                    _("Difference"),                 _("%")),
                  FIMA_POSTINGTYPE_FORECAST.'_'.FIMA_POSTINGTYPE_BUDGET.'_difference_%'                          => sprintf($displaylabel, $types[FIMA_POSTINGTYPE_FORECAST], $types[FIMA_POSTINGTYPE_BUDGET],   _("Difference"),                 _("%")),
                  FIMA_POSTINGTYPE_FORECAST.'_reference_difference_%'                                            => sprintf($displaylabel, $types[FIMA_POSTINGTYPE_FORECAST], _("Reference"),                    _("Difference"),                 _("%")),
                  FIMA_POSTINGTYPE_BUDGET.'_reference_difference_%'                                              => sprintf($displaylabel, $types[FIMA_POSTINGTYPE_BUDGET],   _("Reference"),                    _("Difference"),                 _("%")),
                  'reference_'.FIMA_POSTINGTYPE_ACTUAL.'_difference_%'                                           => sprintf($displaylabel, _("Reference"),                    $types[FIMA_POSTINGTYPE_ACTUAL],   _("Difference"),                 _("%")));

/* Include graphs library. */
$error_reporting = ini_get('error_reporting');
ini_set('error_reporting', $error_reporting & ~E_WARNING);
$graphs = include_once 'Image/Graph.php';
ini_set('error_reporting', $error_reporting);

$title = _("Reports");

switch ($actionID) {
case 'open_report':
case 'display_report':
    if ($params['report_id'] !== null) {
        /* Title. */
        $params['title'] = $_reports[$params['report_id']];

        /* Build report url. */
        $params['url'] = Horde_Util::addParameter(Horde::applicationUrl('report.php'), 'actionID', 'display_report');

        /* Add params from options. */
        $params['graphsize'] = $prefs->getValue('report_graphsize');

        /* Execute report. */
        $report = &Fima_Report::factory($params['report_id'], $params);
        if (is_a($report, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was a problem creating the report: %s."), $report->getMessage()), 'horde.error');
            break;
        }

        if ($params['graph'] && !$params['out']) {
            break;
        }

        $status = $report->execute();
        if (is_a($status, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was a problem executing the report: %s."), $status->getMessage()), 'horde.error');
            break;
        }

        if ($params['graph']) {
            if ($graphs) {
                require FIMA_TEMPLATES . '/reports/img.inc';
            } else {
                $notification->push(_("The graphs library could not be loaded."), 'horde.error');
            }
            exit;
        }

        $title = sprintf(_("Report %s"), $params['title']);
    }
    break;
default:
    break;
}

/* Get date and amount format. */
$datefmt = $prefs->getValue('date_format');
$amountfmt = $prefs->getValue('amount_format');

Horde::addInlineScript(array(
    '$("report_id").focus()'
), 'dom');

require FIMA_TEMPLATES . '/common-header.inc';
require FIMA_TEMPLATES . '/menu.inc';
if ($browser->hasFeature('javascript')) {
    require FIMA_TEMPLATES . '/postings/javascript_edit.inc';
}
require FIMA_TEMPLATES . '/reports/reports.inc';
if (isset($report)) {
    if ($params['graph']) {
        require FIMA_TEMPLATES . '/reports/graph.inc';
    } elseif (count($report->getData()) != 0) {
        require FIMA_TEMPLATES . '/reports/table.inc';
    } else {
        require FIMA_TEMPLATES . '/reports/empty.inc';
    }
}
require $registry->get('templates', 'horde') . '/common-footer.inc';
