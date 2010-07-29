<?php
/**
 * Copyright 2008 Thomas Trethan <thomas@trethan.net>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('FIMA_BASE', dirname(__FILE__));
require_once FIMA_BASE . '/lib/base.php';

/* Get the current action ID. */
$actionID = Horde_Util::getFormData('actionID');

switch ($actionID) {
case 'clear_search':
    unset($_SESSION['fima_search']);
    break;
default:
    break;
}

/* Get search array. */
$search = isset($_SESSION['fima_search']) ? $_SESSION['fima_search'] : array();

/* Set initial values. */
if (!isset($search['type'])) {
    $search['type'] = $prefs->getValue('active_postingtype');
}
if (!isset($search['date_start'])) {
    $search['date_start'] = mktime(0, 0, 0, 1, 1);
} elseif (is_array($search['date_start'])) {
    $search['date_start'] = mktime(0, 0, 0, $search['date_start']['month'], $search['date_start']['day'], (int)$search['date_start']['year']);
}
if (!isset($search['date_end'])) {
    $search['date_end'] = mktime(0, 0, 0, 12, 31);
} elseif (is_array($search['date_end'])) {
    $search['date_end'] = mktime(0, 0, 0, $search['date_end']['month'], $search['date_end']['day'], (int)$search['date_end']['year']);
}
if (!isset($search['asset'])) {
    $search['asset'] = array();
}
if (!isset($search['account'])) {
    $search['account'] = array();
}
if (!isset($search['desc'])) {
    $search['desc'] = '';
}
if (!isset($search['amount_start'])) {
    $search['amount_start'] = '';
}
if (!isset($search['amount_end'])) {
    $search['amount_end'] = '';
}
if (!isset($search['eo'])) {
    $search['eo'] = -1;
}

/* Get posting types and eo. */
$types = Fima::getPostingTypes();
$eos = array('-1' => '',
             '1'  => _("e.o. postings only"),
             '0'  => _("no e.o. postings"));

/* Get date and amount format. */
$datefmt = $prefs->getValue('date_format');
$amountfmt = $prefs->getValue('amount_format');

Horde::addInlineScript(array(
    '$("search_type").focus()'
), 'dom');

$title = _("Search Postings");
require FIMA_TEMPLATES . '/common-header.inc';
require FIMA_TEMPLATES . '/menu.inc';
if ($browser->hasFeature('javascript')) {
    require FIMA_TEMPLATES . '/postings/javascript_edit.inc';
}
require FIMA_TEMPLATES . '/search/search.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
