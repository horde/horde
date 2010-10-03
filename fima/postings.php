<?php
/**
 * Copyright 2008 Thomas Trethan <thomas@trethan.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Thomas Trethan <thomas@trethan.net>
 */

@define('FIMA_BASE', dirname(__FILE__));
require_once FIMA_BASE . '/lib/base.php';

$vars = Horde_Variables::getDefaultVariables();

/* Get the current action ID. */
$actionID = Horde_Util::getFormData('actionID');

/* Change posting type. */
if (($postingtype = Horde_Util::getFormData('postingtype')) !== null) {
    $postingtypeold = $prefs->getValue('active_postingtype');
    $prefs->setValue('active_postingtype', $postingtype);
}

/* Get closed period. */
$closedperiod = (int)$prefs->getValue('closed_period');

/* Create page array. */
$pageOb = array();
$pageOb['url'] = Horde::url('postings.php');
if (($pageOb['page'] = Horde_Util::getFormData('page')) === null) {
    $pageOb['page'] = $prefs->getValue('startpage');
}
$pageOb['mode'] = 'list';

$title = _("My Postings");
$ledger = Fima::getActiveLedger();
$filters = array();

switch ($actionID) {
case 'change_sort':
    /* Sort out the sorting values. */
    if (($sortby = Horde_Util::getFormData('sortby')) !== null) {
        $prefs->setValue('sortby', $sortby);
    }
    if (($sortdir = Horde_Util::getFormData('sortdir')) !== null) {
        $prefs->setValue('sortdir', $sortdir);
    }
    break;

case 'search_postings':
    /* If we're searching, only list those postings that match the search result. */
    $_SESSION['fima_search'] = array('type'         => Horde_Util::getFormData('search_type'),
                                     'date_start'   => Horde_Util::getFormData('search_date_start'),
                                     'date_end'     => Horde_Util::getFormData('search_date_end'),
                                     'asset'        => Horde_Util::getFormData('search_asset'),
                                     'account'      => Horde_Util::getFormData('search_account'),
                                     'desc'         => Horde_Util::getFormData('search_desc'),
                                     'amount_start' => Horde_Util::getFormData('search_amount_start'),
                                     'amount_end'   => Horde_Util::getFormData('search_amount_end'),
                                     'eo'           => Horde_Util::getFormData('search_eo'));

    /* Build filters. */
    if ($_SESSION['fima_search']['type'] !== null) {
        $prefs->setValue('active_postingtype', $_SESSION['fima_search']['type']);
    }
    if ($_SESSION['fima_search']['date_start'] !== null) {
        if (is_array($_SESSION['fima_search']['date_start'])) {
            $_SESSION['fima_search']['date_start'] = mktime(0, 0, 0, $_SESSION['fima_search']['date_start']['month'],
                                                                     $_SESSION['fima_search']['date_start']['day'],
                                                                     $_SESSION['fima_search']['date_start']['year']);
        } else {
            $_SESSION['fima_search']['date_start'] = (int)$_SESSION['fima_search']['date_start'];
        }
    }
    if ($_SESSION['fima_search']['date_end'] !== null) {
        if (is_array($_SESSION['fima_search']['date_end'])) {
            $_SESSION['fima_search']['date_end']   = mktime(0, 0, 0, $_SESSION['fima_search']['date_end']['month'],
                                                                     $_SESSION['fima_search']['date_end']['day'],
                                                                     $_SESSION['fima_search']['date_end']['year']);
        } else {
            $_SESSION['fima_search']['date_end'] = (int)$_SESSION['fima_search']['date_end'];
        }
    }
    if ($_SESSION['fima_search']['asset'] !== null) {
        if (count($_SESSION['fima_search']['asset']) == 0) {
            unset($_SESSION['fima_search']['asset']);
        }
    }
    if ($_SESSION['fima_search']['account'] !== null) {
        if (count($_SESSION['fima_search']['account']) == 0) {
            unset($_SESSION['fima_search']['account']);
        }
    }
    if ($_SESSION['fima_search']['desc'] !== null) {
        if ($_SESSION['fima_search']['desc'] === '') {
            unset($_SESSION['fima_search']['desc']);
        }
    }
    if ($_SESSION['fima_search']['amount_start'] !== null) {
        if ($_SESSION['fima_search']['amount_start'] === '') {
            unset($_SESSION['fima_search']['amount_start']);
        }
    }
    if ($_SESSION['fima_search']['amount_end'] !== null) {
        if ($_SESSION['fima_search']['amount_end'] === '') {
            unset($_SESSION['fima_search']['amount_end']);
        }
    }
    if (isset($_SESSION['fima_search']['amount_start']) && isset($_SESSION['fima_search']['amount_end'])) {
        if ((double)$_SESSION['fima_search']['amount_start'] > (double)$_SESSION['fima_search']['amount_end']) {
            $tmp = $_SESSION['fima_search']['amount_start'];
            $_SESSION['fima_search']['amount_start'] = $_SESSION['fima_search']['amount_end'];
            $_SESSION['fima_search']['amount_end'] = $tmp;
        }
    }
    if ($_SESSION['fima_search']['eo'] !== null) {
        if ($_SESSION['fima_search']['eo'] == -1) {
            unset($_SESSION['fima_search']['eo']);
        }
    }

    break;

case 'clear_search':
    unset($_SESSION['fima_search']);
    break;

case 'add_postings':
    $pageOb['mode'] = 'edit';
    $pageOb['url'] = Horde_Util::addParameter($pageOb['url'], 'actionID', 'add_postings');
    $actionID = 'save_postings';
    $postings = array();
    $title = _("Add Postings");
    break;

case 'edit_postings':
    $postingset = Horde_Util::getFormData('indices');
    if ($postingset !== null) {
        $pageOb['mode'] = 'edit';
        $pageOb['url'] = Horde_Util::addParameter($pageOb['url'], 'actionID', 'add_postings');
        $actionID = 'save_postings';
        $filters[] = array('id', $postingset);
        $title = _("Edit Postings");
    }
    break;

case 'shift_postings':
    $postingset = Horde_Util::getFormData('indices');
    if ($postingset !== null) {
        $pageOb['mode'] = 'shift';
        $actionID = 'update_postings';
        $filters[] = array('id', $postingset);
        $title = _("Shift Postings");
    }
    break;

case 'transfer_postings':
    $pageOb['mode'] = 'transfer';
    $actionID = 'copymove_postings';
    $postings = array();
    $title = _("Transfer Postings");
    break;

case 'save_postings':
    /* Get the form values. */
    $postingset = Horde_Util::getFormData('posting_id');

    $share = &$GLOBALS['fima_shares']->getShare($ledger);
    if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(sprintf(_("Access denied saving postings to %s."), $share->get('name')), 'horde.error');
        Horde::url('postings.php', true)->redirect();
    }
    if ($postingset !== null) {
        $pageOb['mode'] = 'edit';
        $title = _("Edit Postings");
        $posting_owner = $ledger;
        $posting_type = $prefs->getValue('active_postingtype');

        $posting_dates = Horde_Util::getFormData('date');
        $posting_assets = Horde_Util::getFormData('asset');
        $posting_accounts = Horde_Util::getFormData('account');
        $posting_eos = Horde_Util::getFormData('eo');
        $posting_amounts = Horde_Util::getFormData('amount');
        $posting_descs = Horde_Util::getFormData('desc');

        $postings = array();
        $savecount = 0;

        $storage = &Fima_Driver::singleton($ledger);
        foreach($postingset as $index => $posting_id) {
            $posting_valid = true;

            if ($posting_dates[$index] !== '' || $posting_assets[$index] !== '' || $posting_accounts[$index] !== '' ||
                $posting_amounts[$index] !== '' || $posting_descs[$index] !== '') {

                $posting_date = Fima::convertDateToStamp($posting_dates[$index], Fima::convertDateFormat($prefs->getValue('date_format')));
                $posting_asset = $posting_assets[$index];
                $posting_account = $posting_accounts[$index];
                $posting_eo = (int)(bool)$posting_eos[$index];
                $posting_amount = Fima::convertAmountToValue($posting_amounts[$index]);
                $posting_desc = $posting_descs[$index];

                /* Check posting date. */
                if ($posting_date === false) {
                    $posting_valid = false;
                } elseif ($posting_date <= $closedperiod) {
                    $posting_valid = false;
                }

                /* Check asset account and account. */
                if ($posting_asset === '' || $posting_account === '') {
                    $posting_valid = false;
                } elseif ($posting_asset === $posting_account) {
                    continue;
                }

                /* Fix amount sign. */
                if ($prefs->getValue('expenses_sign') == 0) {
                    $account = Fima::getAccount($posting_account);
                    if (!is_a($account, 'PEAR_Error') && $account !== null) {
                        if ($account['type'] == FIMA_ACCOUNTTYPE_EXPENSE) {
                            $posting_amount *= -1;
                        }
                    }
                }

                /* If $posting_id is set, we're modifying an existing account. Otherwise,
                 * we're adding a new posting with the provided attributes. */
                if ($posting_valid) {
                    if ($posting_id != null) {
                        $result = $storage->modifyPosting($posting_id, $posting_type, $posting_date, $posting_asset,
                                                          $posting_account, $posting_eo, $posting_amount, $posting_desc);
                    } else {
                        $result = $storage->addPosting($posting_type, $posting_date, $posting_asset, $posting_account,
                                                       $posting_eo, $posting_amount, $posting_desc);
                    }

                    // Check our results.
                    if (is_a($result, 'PEAR_Error')) {
                        $notification->push(sprintf(_("There was a problem saving the posting: %s."), $result->getMessage()), 'horde.error');
                        $posting_valid = false;
                    } else {
                        $savecount++;
                    }
                }

                /* Reload invalid or unsaved postings. */
                if (!$posting_valid) {
                    $postings[] = array('posting_id' => $posting_id,
                                        'owner' => $ledger,
                                        'type' => $posting_type,
                                        'date' => $posting_date,
                                        'asset' => $posting_asset,
                                        'account' => $posting_account,
                                        'eo' => $posting_eo,
                                        'amount' => $posting_amount,
                                        'desc' => $posting_desc);
                }
            }
        }

        if ($savecount > 0) {
            $notification->push(sprintf(_("Saved %d postings."), $savecount), 'horde.success');
        }
        if (count($postings) > 0) {
            $notification->push(sprintf(_("%d postings not saved."), count($postings)), 'horde.error');
        } else {
            /* Return to the posting list. */
            Horde::url('postings.php', true)->redirect();
        }
    } else {
        /* Return to the posting list. */
        Horde::url('postings.php', true)->redirect();
    }
    break;

case 'delete_postings':
    /* Delete postings if we're provided with valid account IDs. */
    $postingset = Horde_Util::getFormData('indices');

    $share = &$GLOBALS['fima_shares']->getShare($ledger);
    if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
        $notification->push(sprintf(_("Access denied deleting postings from %s."), $share->get('name')), 'horde.error');
        Horde::url('postings.php', true)->redirect();
    }
    if ($postingset !== null) {
        $storage = &Fima_Driver::singleton($ledger);
        $delcount = 0;
        foreach($postingset as $index => $posting_id) {
            $result = $storage->deletePosting($posting_id);
            if (is_a($result, 'PEAR_Error')) {
                $notification->push(sprintf(_("There was a problem deleting posting #%d: %s"),
                                            $index, $result->getMessage()), 'horde.error');
            } else {
                $delcount++;
            }
        }
        if ($delcount > 0) {
            $notification->push(sprintf(_("Deleted %d postings."), $delcount), 'horde.success');
        }
    }

    /* Return to the posting list. */
    Horde::url('postings.php', true)->redirect();

case 'update_postings':
    /* Get the form values. */
    $postingset = Horde_Util::getFormData('posting_id');

    $share = &$GLOBALS['fima_shares']->getShare($ledger);
    if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(sprintf(_("Access denied shifting postings in %s."), $share->get('name')), 'horde.error');
        Horde::url('postings.php', true)->redirect();
    }
    if ($postingset !== null) {
        $posting_type = Horde_Util::getFormData('type');
        $posting_asset = Horde_Util::getFormData('asset');
        $posting_account = Horde_Util::getFormData('account');

        if ($posting_type || $posting_asset || $posting_account) {
            $storage = &Fima_Driver::singleton($ledger);
            $shiftcount = 0;

            foreach($postingset as $index => $posting_id) {
                $result = $storage->ShiftPosting($posting_id, $posting_type, $posting_asset, $posting_account);
                if (is_a($result, 'PEAR_Error')) {
                    $notification->push(sprintf(_("There was a problem shifting posting #%d: %s"),
                                                  $index, $result->getMessage()), 'horde.error');
                } else {
                    $shiftcount++;
                }
            }
            if ($shiftcount > 0) {
                $notification->push(sprintf(_("Shifted %d postings."), $shiftcount), 'horde.success');
            }
        }
    }

    /* Return to the posting list. */
    Horde::url('postings.php', true)->redirect();

case 'copymove_postings':
    $share = &$GLOBALS['fima_shares']->getShare($ledger);
    if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(sprintf(_("Access denied transfering postings in %s."), $share->get('name')), 'horde.error');
        Horde::url('postings.php', true)->redirect();
    }
    if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE) && (!Horde_Util::getFormData('keep') || Horde_Util::getFormData('delete'))) {
        $notification->push(sprintf(_("Access denied transfering postings in %s."), $share->get('name')), 'horde.error');
        Horde::url('postings.php', true)->redirect();
    }
    $type_from = Horde_Util::getFormData('type_from');
    $period_from = Horde_Util::getFormData('period_from');
    $keep = Horde_Util::getFormData('keep');
    $summarize = Horde_Util::getFormData('summarize');
    $summarize_account = Horde_Util::getFormData('summarize_post_account');
    $type_to = Horde_Util::getFormData('type_to');
    $period_to = Horde_Util::getFormData('period_to');
    $delete = Horde_Util::getFormData('delete');

    $period_from_start = mktime(0, 0, 0, ($period_from['month'] === '') ? 1 : $period_from['month'], 1, (int)$period_from['year']);
    $period_from_end = mktime(0, 0, 0, ($period_from['month'] === '') ? 12 : $period_from['month'] + 1, ($period_from['month'] === '') ? 31 : 0, (int)$period_from['year']);
    $period_to_start = mktime(0, 0, 0, ($period_to['month'] === '') ? 1 : $period_to['month'], 1, (int)$period_to['year']);
    $period_to_end = mktime(0, 0, 0, ($period_to['month'] === '') ? 12 : $period_to['month'] + 1, ($period_to['month'] === '') ? 31 : 0, (int)$period_to['year']);

    $storage = &Fima_Driver::singleton($ledger);

    /* Delete existing. */
    if ($delete) {
        $transferfilters = array(array('type', $type_to),
                                 array('date', $period_to_start, '>='),
                                 array('date', $period_to_end, '<='));
        $postings = Fima::listPostings($transferfilters);
        $delcount = 0;
        foreach ($postings as $postingId => $posting) {
            $result = $storage->deletePosting($postingId);
            if (is_a($result, 'PEAR_Error')) {
                $notification->push(sprintf(_("There was a problem deleting an existing posting: %s"),
                                            $result->getMessage()), 'horde.error');
            } else {
                $delcount++;
            }
        }
        if ($delcount > 0) {
            $notification->push(sprintf(_("Deleted %d existing postings."), $delcount), 'horde.success');
        }
    }

    /* Copy postings. */
    $transferfilters = array(array('type', $type_from),
                             array('date', $period_from_start, '>='),
                             array('date', $period_from_end, '<='));
    $postings = Fima::listPostings($transferfilters);

    if ($summarize != 'none') {
        $accounts = Fima::listAccounts();
        $postingscopy = array();

        foreach ($postings as $postingId => $posting) {
            $asset = (isset($accounts[$posting['asset']]))
                ? (($accounts[$posting['asset']]['parent_id'] !== null) ? $accounts[$posting['asset']]['parent_id'] : $accounts[$posting['asset']]['account_id'])
                : $posting['asset'];
            $account = (isset($accounts[$posting['account']]))
                ? (($accounts[$posting['account']]['parent_id'] !== null) ? $accounts[$posting['account']]['parent_id'] : $accounts[$posting['account']]['account_id'])
                : $posting['account'];

            if ($summarize == 'combine') {
                $copyId = $asset . '_' . $account . '_' . strftime('%Y%m', $posting['date']);

                if (isset($postingscopy[$copyId])) {
                    $postingscopy[$copyId]['amount'] += $posting['amount'];
                } else {
                    $postingscopy[$copyId] = $posting;
                    $postingscopy[$copyId]['date'] = mktime(0, 0, 0, ($period_to['month'] === '') ? strftime('%m', $posting['date']) : $period_to['month'], 1, (int)$period_to['year']);
                    $postingscopy[$copyId]['asset'] = $asset;
                    $postingscopy[$copyId]['account'] = $account;
                    $postingscopy[$copyId]['eo'] = 0;
                    $postingscopy[$copyId]['desc'] = _("Summarized");
                }
            } elseif ($summarize == 'post') {
                $copyIdAsset = $asset . '_' . strftime('%Y%m', $posting['date']);
                $copyIdAccount = $account . '_' . strftime('%Y%m', $posting['date']);

                if (isset($postingscopy[$copyIdAsset])) {
                    $postingscopy[$copyIdAsset]['amount'] += $posting['amount'];
                } else {
                    $postingscopy[$copyIdAsset] = $posting;
                    $postingscopy[$copyIdAsset]['date'] = mktime(0, 0, 0, ($period_to['month'] === '') ? strftime('%m', $posting['date']) : $period_to['month'], 1, (int)$period_to['year']);
                    $postingscopy[$copyIdAsset]['asset'] = $asset;
                    $postingscopy[$copyIdAsset]['account'] = $summarize_account;
                    $postingscopy[$copyIdAsset]['eo'] = 0;
                    $postingscopy[$copyIdAsset]['desc'] = _("Summarized");
                }

                if (isset($postingscopy[$copyIdAccount])) {
                    $postingscopy[$copyIdAccount]['amount'] += $posting['amount'];
                } else {
                    $postingscopy[$copyIdAccount] = $posting;
                    $postingscopy[$copyIdAccount]['date'] = mktime(0, 0, 0, ($period_to['month'] === '') ? strftime('%m', $posting['date']) : $period_to['month'], 1, (int)$period_to['year']);
                    $postingscopy[$copyIdAccount]['asset'] = $summarize_account;
                    $postingscopy[$copyIdAccount]['account'] = $account;
                    $postingscopy[$copyIdAccount]['eo'] = 0;
                    $postingscopy[$copyIdAccount]['desc'] = _("Summarized");
                }
            }
        }
    } else {
        $postingscopy = &$postings;
        foreach ($postingscopy as $postingId => $posting) {
            $postingscopy[$postingId]['date'] = mktime(0, 0, 0, ($period_to['month'] === '') ? strftime('%m', $posting['date']) : $period_to['month'], strftime('%d', $posting['date']), (int)$period_to['year']);
        }
    }

    $addcount = 0;
    foreach ($postingscopy as $postingId => $posting) {
        $result = $storage->addPosting($type_to, $posting['date'], $posting['asset'], $posting['account'],
                                       $posting['eo'], $posting['amount'], $posting['desc']);

        // Check our results.
        if (is_a($result, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was a problem saving the posting: %s."), $result->getMessage()), 'horde.error');
        } else {
            $addcount++;
        }
    }
    if ($addcount > 0) {
        $notification->push(sprintf($summarize ? _("Summarized %d postings.") : _("Transfered %d postings."), $addcount), 'horde.success');
    }

    /* Delete original postings. */
    if (!$keep) {
        $delcount = 0;
        foreach ($postings as $postingId => $posting) {
            $result = $storage->deletePosting($postingId);
            if (is_a($result, 'PEAR_Error')) {
                $notification->push(sprintf(_("There was a problem deleting an original posting: %s"),
                                            $result->getMessage()), 'horde.error');
            } else {
                $delcount++;
            }
        }
        if ($delcount > 0) {
            $notification->push(sprintf(_("Deleted %d original postings."), $delcount), 'horde.success');
        }
    }

    /* Return to the posting list. */
    Horde::url('postings.php', true)->redirect();

default:
    break;
}

/* Print. */
$print_view = (bool)Horde_Util::getFormData('print');
if (!$print_view && $pageOb['mode'] == 'list') {
    $print_link = Horde_Util::addParameter(Horde::url('postings.php'), array('print' => 1));
}

/* Filters. */
$postingtype = $prefs->getValue('active_postingtype');
$filters[] = array('type', $postingtype);
if (isset($_SESSION['fima_search'])) {
    $title = _("Search Results");
    foreach ($_SESSION['fima_search'] as $searchId => $search) {
        if ($search === null) {
            continue;
        }
        switch ($searchId) {
        case 'date_start':   $filters[] = array('date', $search, '>='); break;
        case 'date_end':     $filters[] = array('date', $search, '<='); break;
        case 'asset':		 $filters[] = array(array(array('asset', $search), array('account', $search, '=', 'OR'))); break;
        case 'account':      $filters[] = array('account', $search); break;
        case 'desc':         $filters[] = array('desc', Fima::convertWildcards($search), 'LIKE'); break;
        case 'amount_start': $filters[] = array('amount', Fima::convertAmountToValue($search), '>='); break;
        case 'amount_end':   $filters[] = array('amount', Fima::convertAmountToValue($search), '<='); break;
        case 'eo':           $filters[] = array('eo', (int)(bool)$search);
        default:             break;
        }
    }
}

/* Retrieve accounts, accounttypes and postings (if not set before). */
$accounts = Fima::listAccounts();
$accounttypes = Fima::getAccountTypes();
if (!isset($postings)) {
    $postings = Fima::listPostings($filters, ($pageOb['mode'] != 'list' || $print_view) ? null : $pageOb['page']);
}

$pageOb['postings_perpage'] = $prefs->getValue('max_postings');
$pageOb['postings_total'] = Fima::getPostingsCount();

if ($pageOb['mode'] == 'edit') {
    /* Fix amount sign. */
    if ($prefs->getValue('expenses_sign') == 0) {
        foreach ($postings as $postingId => $posting) {
            if ($accounts[$posting['account']]['type'] == FIMA_ACCOUNTTYPE_EXPENSE) {
                $postings[$postingId]['amount'] *= -1;
            }
        }
    }
    /* Add blank postings. */
    for ($i = count($postings); $i < max($pageOb['postings_perpage'], 12); $i++) {
        $postings[] = array('posting_id' => null,
                            'owner' => $ledger,
                            'type' => $postingtype,
                            'date' => null,
                            'asset' => null,
                            'account' => null,
                            'eo' => null,
                            'amount' => null,
                            'desc' => null);
    }
}

/* Add account information to postings and create flags list. */
if ($pageOb['mode'] == 'list') {
    $flags = array();
    foreach ($postings as $postingId => $posting) {
        $postings[$postingId]['desc'] = htmlspecialchars($posting['desc']);

        if (isset($accounts[$posting['asset']])) {
            $postings[$postingId]['asset_label'] = htmlspecialchars($accounts[$posting['asset']]['label']);
            $postings[$postingId]['asset_closed'] = $accounts[$posting['asset']]['closed'];
        } else {
            $postings[$postingId]['asset_label'] = _("Unknown");
            $postings[$postingId]['asset_closed'] = false;
        }
        if (isset($accounts[$posting['account']])) {
            $postings[$postingId]['account_label'] = htmlspecialchars($accounts[$posting['account']]['label']);
            $postings[$postingId]['account_type'] = $accounts[$posting['account']]['type'];
            $postings[$postingId]['account_type_eo'] = sprintf($posting['eo'] ? _("e.o. %s") : _("%s") , $accounttypes[$accounts[$posting['account']]['type']]);
            $postings[$postingId]['account_closed'] = $accounts[$posting['account']]['closed'];
        } else {
            $postings[$postingId]['account_label'] = _("Unknown");
            $postings[$postingId]['account_type'] = '';
            $postings[$postingId]['account_type_eo'] = '';
            $postings[$postingId]['account_closed'] = false;
        }

        $flag = 0;
        $flagpos = 0;
        foreach ($accounttypes as $typeId => $typeLabel) {
            if ($postings[$postingId]['account_type'] == $typeId) {
                $flag |= pow(2, $flagpos);
            }
            $flagpos++;
        }
        $flags[] = $flag;
    }
}

/* Set up page information. */
$pageOb['page_count'] = ceil($pageOb['postings_total'] / $pageOb['postings_perpage']);
if ($pageOb['page'] < 0) {
    $pageOb['page'] += $pageOb['page_count'] + 1;
}
if ($pageOb['mode'] == 'list') {
    if ($pageOb['postings_total'] == 0) {
        $pageOb['postings_count'] = _("No Postings");
    } else {
        $pageOb['postings_count'] = sprintf(_("%s to %s of %s Postings"),
                                            ($pageOb['page'] - 1) * $pageOb['postings_perpage'] + 1,
                                            min($pageOb['page'] * $pageOb['postings_perpage'], $pageOb['postings_total']),
                                            $pageOb['postings_total']);
    }
}

/* Get sorting. */
if ($pageOb['mode'] == 'list' || $pageOb['mode'] == 'edit') {
    $sortby = $prefs->getValue('sortby');
    $sortdir = $prefs->getValue('sortdir');
    $sorturl = Horde_Util::addParameter($pageOb['url'], 'sortdir', ($sortdir) ? 0 : 1);
}

/* Generate page links. */
if ($pageOb['mode'] == 'list') {
    $graphicsdir = Horde_Themes::img(null, 'horde');
    if ($pageOb['page'] == 1) {
        $pageOb['pages_first'] = Horde::img('nav/first-grey.png', null, null, $graphicsdir);
        $pageOb['pages_prev'] = Horde::img('nav/left-grey.png', null, null, $graphicsdir);
    } else {
        $first_url = Horde_Util::addParameter($pageOb['url'], 'page', 1);
        $pageOb['pages_first'] = Horde::link($first_url, _("First Page")) . Horde::img('nav/first.png', '<<', null, $graphicsdir) . '</a>';
        $prev_url = Horde_Util::addParameter($pageOb['url'], 'page', $pageOb['page'] - 1);
        $pageOb['pages_prev'] = Horde::link($prev_url, _("Previous Page"), '', '', '', '', '', array('id' => 'prev')) . Horde::img('nav/left.png', '<', null, $graphicsdir) . '</a>';
    }
    if ($pageOb['page'] == $pageOb['page_count']) {
        $pageOb['pages_last'] = Horde::img('nav/last-grey.png', null, null, $graphicsdir);
        $pageOb['pages_next'] = Horde::img('nav/right-grey.png', null, null, $graphicsdir);
    } else {
        $next_url = Horde_Util::addParameter($pageOb['url'], 'page', $pageOb['page'] + 1);
        $pageOb['pages_next'] = Horde::link($next_url, _("Next Page"), '', '', '', '', '', array('id' => 'next')) . Horde::img('nav/right.png', '>', null, $graphicsdir) . '</a>';
        $last_url = Horde_Util::addParameter($pageOb['url'], 'page', $pageOb['page_count']);
        $pageOb['pages_last'] = Horde::link($last_url, _("Last Page")) . Horde::img('nav/last.png', '>>', null, $graphicsdir) . '</a>';
    }
}

/* Some browsers have trouble with hidden overflow in table cells but not in divs. */
if ($GLOBALS['browser']->hasQuirk('no_hidden_overflow_tables')) {
    $overflow_begin = '<div class="ohide">';
    $overflow_end = '</div>';
} else {
    $overflow_begin = '';
    $overflow_end = '';
}

/* Set up row Ids. */
$rowId = 0;

/* Get date and amount format. */
$datefmt = $prefs->getValue('date_format');
$amountfmt = $prefs->getValue('amount_format');

$js_onload = array();

if ($pageOb['mode'] == 'edit') {
    /* Fix date format. */
    $datefmt = Fima::convertDateFormat($datefmt);

    /* Add current date in first field if no postings. */
    foreach ($postings as $key => $value) {
        if ($value['date'] == '') {
            $js_onload[] = '$("date1").setValue(' . Horde_Serialize::serialize(strftime($datefmt), Horde_Serialize::JSON) . ')';
        }
        break;
    }

    /* Select first date field. */
    $js_onload[] = 'updateResult()';
    $js_onload[] = 'updateAssetResult(_getall("asset[]")[0])';
    $js_onload[] = '$("date1").focus().select()';
}

Horde::addInlineScript($js_onload, 'dom');

require FIMA_TEMPLATES . '/common-header.inc';
if ($print_view) {
    require_once $registry->get('templates', 'horde') . '/javascript/print.js';
} else {
    require FIMA_TEMPLATES . '/menu.inc';
}
if ($browser->hasFeature('javascript')) {
    require FIMA_TEMPLATES . '/postings/javascript_' . $pageOb['mode'] . '.inc';
}

/* Get current asset results. */
if ($pageOb['mode'] == 'edit') {
    $assetresults = Fima::getAssetResults($prefs->getValue('active_postingtype'));
}

/* Generate tabs. */
if ($pageOb['mode'] != 'transfer' && !$print_view) {
    $tabs = new Horde_Core_Ui_Tabs('postingtype', $vars);
    $postingtypes = Fima::getPostingTypes();
    foreach ($postingtypes as $typeValue => $typeLabel) {
        $tabs->addTab($typeLabel, $pageOb['url'], $typeValue);
    }
    echo $tabs->render($prefs->getValue('active_postingtype'));
}

/* Generate list. */
if (!$print_view) {
    require FIMA_TEMPLATES . '/postings/header.inc';
}

if ($pageOb['mode'] == 'list' && $pageOb['page_count'] == 0) {
    require FIMA_TEMPLATES . '/postings/empty.inc';
} else {
    $form = 1;
    if (!$print_view) {
        require FIMA_TEMPLATES . '/postings/navbar.inc';
        require FIMA_TEMPLATES . '/postings/actions.inc';
    }

    require FIMA_TEMPLATES . '/postings/posting_headers.inc';
    require FIMA_TEMPLATES . '/postings/' . $pageOb['mode'] . '.inc';
    require FIMA_TEMPLATES . '/postings/posting_footers.inc';

    /* If there are 20 postings or less, don't show the actions/navbar again. */
    if ((count($postings) > 20 || $pageOb['mode'] != 'list') && !$print_view) {
        $form = 2;
        require FIMA_TEMPLATES . '/postings/actions.inc';
        require FIMA_TEMPLATES . '/postings/navbar.inc';
    } else {
        /* TODO */
        echo '<tr><td class="control" colspan="6"></td></tr>';
    }
}
require FIMA_TEMPLATES . '/postings/footer.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';
