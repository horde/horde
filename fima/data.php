<?php
/**
 * Copyright 2008 Thomas Trethan <thomas@trethan.net>
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */

function _cleanupData()
{
    $GLOBALS['import_step'] = 1;
    return Horde_Data::IMPORT_FILE;
}

@define('FIMA_BASE', dirname(__FILE__));
require_once FIMA_BASE . '/lib/base.php';

$ledger = Fima::getActiveLedger();

/* Importable file types. */
$file_types = array('csv'      => _("CSV"),
                    'tsv'      => _("TSV"));

/* Templates for the different import steps. */
$templates = array(
    Horde_Data::IMPORT_CSV => array($registry->get('templates', 'horde') . '/data/csvinfo.inc'),
    Horde_Data::IMPORT_TSV => array($registry->get('templates', 'horde') . '/data/tsvinfo.inc'),
    Horde_Data::IMPORT_MAPPED => array($registry->get('templates', 'horde') . '/data/csvmap.inc'),
    Horde_Data::IMPORT_DATETIME => array($registry->get('templates', 'horde') . '/data/datemap.inc'),
    Horde_Data::IMPORT_FILE => array(FIMA_TEMPLATES . '/data/import.inc', FIMA_TEMPLATES . '/data/export.inc'),
);

/* Field/clear name mapping. */
$app_fields = array('date'     => _("Date"),
                    'asset'    => _("Asset Account"),
                    'account'  => _("Account"),
                    'desc'     => _("Description"),
                    'amount'   => _("Amount"),
                    'eo'       => _("e.o."));

/* Date/time fields. */
$time_fields = array('date' => 'date');

/* Initial values. */
$param = array('time_fields' => $time_fields,
               'file_types'  => $file_types);
$import_format = Horde_Util::getFormData('import_format', '');
$import_step   = Horde_Util::getFormData('import_step', 0) + 1;
$next_step     = Horde_Data::IMPORT_FILE;
$actionID      = Horde_Util::getFormData('actionID');
$error         = false;

/* Loop through the action handlers. */
switch ($actionID) {
case 'export':
    $data = array();

    /* Create a Fima storage instance. */
    $storage = &Fima_Driver::singleton($ledger);
    if (is_a($storage, 'PEAR_Error')) {
        $notification->push(sprintf(_("Failed to access the ledger: %s"), $storage->getMessage()), 'horde.error');
        $error = true;
        break;
    }
    $params = $storage->getParams();

    $filters = array(array('type', $prefs->getValue('active_postingtype')));

    /* Get accounts and postings. */
    $accounts = Fima::listAccounts();
    $postings = Fima::listPostings($filters);

    foreach ($postings as $postingId => $posting) {
        $row = array();
        foreach ($posting as $key => $value) {
            switch ($key) {
            case 'date':
                $row[$key] = strftime(Fima::convertDateFormat($prefs->getValue('date_format')), $value);
                break;
            case 'asset':
            case 'account':
                $row[$key] = isset($accounts[$value]) ? $accounts[$value]['number'] : '';
                break;
            case 'amount':
                $row[$key] = Fima::convertValueToAmount($value);
                break;
            case 'eo':
            case 'desc':
                $row[$key] = Horde_String::convertCharset($value, $GLOBALS['registry']->getCharset(), $params['charset']);
                break;
            default:
                break;
            }
        }
        $data[] = $row;
    }

    if (!count($data)) {
        $notification->push(_("There were no postings to export."), 'horde.message');
        $error = true;
        break;
    }

    switch (Horde_Util::getFormData('exportID')) {
    case EXPORT_CSV:
        $injector->getInstance('Horde_Data')->getData('Csv', array('cleanup' => '_cleanupData'))->exportFile(_("postings.csv"), $data, true);
        exit;

    case EXPORT_TSV:
        $injector->getInstance('Horde_Data')->getData('Tsv', array('cleanup' => '_cleanupData'))->exportFile(_("postings.tsv"), $data, true);
        exit;
    }
    break;

case Horde_Data::IMPORT_FILE:
    $storage = &Fima_Driver::singleton($ledger);
    if (is_a($storage, 'PEAR_Error')) {
        $notification->push(sprintf(_("Failed to access the ledger: %s"), $storage->getMessage()), 'horde.error');
        $error = true;
        break;
    }

    $_SESSION['import_data']['target'] = $ledger;
    $_SESSION['import_data']['purge'] = Horde_Util::getFormData('purge');
    break;
}

if (!$error) {
    try {
        $data = $injector->getInstance('Horde_Data')->getData($import_format, array('cleanup' => '_cleanupData'));
        $next_step = $data->nextStep($actionID, $param);
    } catch (Horde_Data_Exception $e) {
        if ($data) {
            $notification->push($e, 'horde.error');
            $next_step = $data->cleanup();
        } else {
            $notification->push(_("This file format is not supported."), 'horde.error');
            $next_step = Horde_Data::IMPORT_FILE;
        }
    }
}

/* We have a final result set. */
if (is_array($next_step)) {
    /* Create a Fima storage instance. */
    $storage = &Fima_Driver::singleton($ledger);
    if (is_a($storage, 'PEAR_Error')) {
        $notification->push(sprintf(_("Failed to access the ledger: %s"), $storage->getMessage()), 'horde.error');
    }

    $params = $storage->getParams();

    /* Purge old postings if requested. */
    if ($_SESSION['import_data']['purge']) {
        $result = $storage->deleteAll(false, $prefs->getValue('active_postingtype'));
        if (is_a($result, 'PEAR_Error')) {
            $notification->push(sprintf(_("The postings could not be purged: %s"), $result->getMessage()), 'horde.error');
        } else {
            $notification->push(_("Postings successfully purged."), 'horde.success');
        }
    }

    /* Get accounts and postings. */
    $accounts = Fima::listAccounts();
    $accounts_indices = array();
    foreach ($accounts as $account) {
        $accounts_indices[$account['number']] = $account['account_id'];
    }

    foreach ($next_step as $row) {
        $row['type'] = $prefs->getValue('active_postingtype');
        $row['asset'] = sprintf('%\'04d', $row['asset']);
        $row['asset'] = isset($accounts_indices[$row['asset']]) ? $accounts_indices[$row['asset']] : null;
        $row['account'] = sprintf('%\'04d', $row['account']);
        $row['account'] = isset($accounts_indices[$row['account']]) ? $accounts_indices[$row['account']] : null;
        $row['date'] = Fima::convertDateToStamp($row['date'], Fima::convertDateFormat($prefs->getValue('date_format')));
        $row['amount'] = Fima::convertAmountToValue($row['amount']);
        if ($prefs->getValue('expenses_sign') == 0) {
            if ($row['account'] !== null) {
                if ($accounts[$row['account']]['type'] == FIMA_ACCOUNTTYPE_EXPENSE) {
                    $row['amount'] *= -1;
                }
            } else {
                $row['amount'] *= -1;
            }
        }
        $row['desc'] = isset($row['desc']) ? trim($row['desc']) : '';
        $row['eo'] = isset($row['eo']) ? (bool)trim($row['eo']) : false;
        $result = $storage->addPosting($row['type'], $row['date'], $row['asset'], $row['account'], $row['eo'], $row['amount'], $row['desc']);
        if (is_a($result, 'PEAR_Error')) {
            break;
        }
    }

    if (!count($next_step)) {
        $notification->push(sprintf(_("The %s file didn't contain any postings."),
                                    $file_types[$_SESSION['import_data']['format']]), 'horde.error');
    } else {
        $notification->push(sprintf(_("%s successfully imported"),
                                    $file_types[$_SESSION['import_data']['format']]), 'horde.success');
    }
    $next_step = $data->cleanup();
}

$title = _("Import/Export Postings");
require FIMA_TEMPLATES . '/common-header.inc';
require FIMA_TEMPLATES . '/menu.inc';

if ($next_step == Horde_Data::IMPORT_FILE) {
    /* Build the charset options. */
    $charsets = $registry->nlsconfig['encodings'];
    asort($charsets);
    $all_charsets = $registry->nlsconfig['charsets'];
    natcasesort($all_charsets);
    foreach ($all_charsets as $charset) {
        if (!isset($charsets[$charset])) {
            $charsets[$charset] = $charset;
        }
    }
    $my_charset = $GLOBALS['registry']->getCharset(true);
}

foreach ($templates[$next_step] as $template) {
    require $template;
    echo '<br />';
}
require $registry->get('templates', 'horde') . '/common-footer.inc';
