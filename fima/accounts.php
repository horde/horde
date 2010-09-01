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
require_once 'Horde/Tree.php';

/* Get ledger. */
$ledger = Fima::getActiveLedger();
$share = &$GLOBALS['fima_shares']->getShare($ledger);
if (is_a($share, 'PEAR_Error')) {
    $notification->push(sprintf(_("Access denied on accounts: %s"), $share->getMessage()), 'horde.error');
}

/* Run through the action handlers. */
$actionID = Horde_Util::getFormData('actionID');
switch ($actionID) {
case 'delete_all':
    if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
        $notification->push(_("Access denied deleting all accounts and postings."), 'horde.error');
    } else {
        $storage = &Fima_Driver::singleton($ledger);

        /* Delete all. */
        $result = $storage->deleteAll();
        if (is_a($result, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was a problem deleting all accounts and postings: %s"),
                                        $result->getMessage()), 'horde.error');
        } else {
            $notification->push(_("Deleted all accounts and postings."), 'horde.success');
        }
        break;
    }
default:
    break;
}

/* Prepare account folder structure */
$account_url = Horde::url('account.php');;
$view_url = Horde_Util::addParameter(Horde::url('postings.php'), 'actionID', 'search_postings');

$accounts = array();
$accounts['root'] = array('account_id' => 'root', 'owner' => $ledger, 'number' => '', 'type' => 'root', 'name' => $share->get('name'), 'desc' => '', 'icon' => 'accounts.png', 'closed' => false, 'expanded' => true, 'parent_id' => null);

$types = Fima::getAccountTypes();
foreach ($types as $typeId => $typeLabel) {
    $accounts[$typeId] = array('account_id' => $typeId, 'owner' => $ledger, 'number' => '', 'type' => $typeId, 'name' => $typeLabel, 'desc' => '', 'icon' => $typeId . '.png', 'closed' => false, 'expanded' => true, 'parent_id' => 'root', 'view_link' => Horde_Util::addParameter($view_url, 'search_type', $typeId), 'add_link' => Horde_Util::addParameter($account_url, array('account' => $typeId, 'actionID' => 'add_account')));
}

/* Get accounts. */
$accountlist = Fima::listAccounts();
foreach ($accountlist as $accountId => $account) {
    $accounts[$accountId] = $account;

    $accounts[$accountId]['view_link'] = Horde_Util::addParameter($view_url, $account['type'] == FIMA_ACCOUNTTYPE_ASSET ? 'search_asset' : 'search_account', $account['account_id']);
    $account_url_account = Horde_Util::addParameter($account_url, 'account', $account['account_id']);
    $accounts[$accountId]['add_link'] = Horde_Util::addParameter($account_url_account, 'actionID', 'add_account');
    $accounts[$accountId]['edit_link'] = Horde_Util::addParameter($account_url_account, 'actionID', 'modify_account');
    $accounts[$accountId]['delete_link'] = Horde_Util::addParameter($account_url_account, 'actionID', 'delete_account');

    if ($account['parent_id'] !== null && isset($accounts[$account['parent_id']])) {
        unset($accounts[$accountId]['add_link']);
    } else {
        $accounts[$accountId]['parent_id'] = $account['type'];
        $accounts[$accountId]['parent_number'] = '';
        $accounts[$accountId]['parent_name'] = '';
    }

    $accounts[$accountId]['icon'] = $accounts[$accounts[$accountId]['parent_id']]['icon'];
    $accounts[$accountId]['closed'] = $account['closed'];
    $accounts[$accountId]['expanded'] = false;
}

/* Print. */
$print_view = (bool)Horde_Util::getFormData('print');
if (!$print_view) {
    $print_link = Horde::url(Horde_Util::addParameter('accounts.php', array('print' => 1)));
}

Horde::addScriptFile('tables.js', 'horde');
$title = _("My Accounts");
require FIMA_TEMPLATES . '/common-header.inc';

if ($print_view) {
    require_once $registry->get('templates', 'horde') . '/javascript/print.js';
} else {
    require FIMA_TEMPLATES . '/menu.inc';
}

require FIMA_TEMPLATES . '/accounts/accounts.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
