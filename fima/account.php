<?php
/**
 * Copyright 2008 Thomas Trethan <thomas@trethan.net>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('FIMA_BASE', dirname(__FILE__));
require_once FIMA_BASE . '/lib/base.php';
require_once FIMA_BASE . '/lib/Forms/account.php';
$vars = Horde_Variables::getDefaultVariables();

/* Redirect to the account list if no action has been requested. */
$actionID = $vars->get('actionID');
if (is_null($actionID)) {
    Horde::applicationUrl('accounts.php', true)->redirect();
}

/* Get ledger. */
$ledger = Fima::getActiveLedger();
$share = &$GLOBALS['fima_shares']->getShare($ledger);
if (is_a($share, 'PEAR_Error')) {
    $notification->push(sprintf(_("Access denied on account: %s"), $share->getMessage()), 'horde.error');
    Horde::applicationUrl('accounts.php', true)->redirect();
}
$ledger_name = $share->get('name');

/* Run through the action handlers. */
switch ($actionID) {
case 'add_account':
    $vars->set('actionID', 'save_account');

    /* Preset account attributes regarding its parent. */
    $parent_id = $vars->get('account');
    $vars->set('parent_id', $parent_id);
    if (isset($parent_id)) {
        $account_types = Fima::getAccountTypes();
        if (isset($account_types[$parent_id])) {
            $vars->set('type', $parent_id);
        } else {
            $parent = Fima::getAccount($parent_id);
            if (!is_a($parent, 'PEAR_Error')) {
                if (Fima::getAccountParent($parent['number']) === null) {
                    $accounts = Fima::listAccounts();
                    $tmp = '';
                    foreach ($accounts as $accountId => $account) {
                        if ((int)$account['number'] >= $parent['number'] + 100) {
                            break;
                        }
                        $tmp = $account['number'] + 1;
                    }
                    if (Fima::getAccountParent($tmp) == $parent['number']) {
                        $vars->set('number', sprintf('%\'04d', $tmp));
                    }
                }
                $vars->set('type', $parent['type']);
            }
        }
    }
    $vars->set('number_new', $vars->get('number'));
    
    $form = new Fima_AccountForm($vars, _("New Account"));
    break;

case 'modify_account':
    $account_id = $vars->get('account');
    if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(_("Access denied editing account."), 'horde.error');
    } else {
        $account = Fima::getAccount($account_id);
        if (!isset($account) || !isset($account['account_id'])) {
            $notification->push(_("Account not found."), 'horde.error');
        } else {
            $vars = new Horde_Variables($account);
            $vars->set('actionID', 'save_account');
            $vars->set('number_new', $vars->get('number'));
            $form = new Fima_AccountForm($vars, sprintf(_("Edit: %s"), trim($account['number'] . ' ' . $account['name'])), $share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE));
            break;
        }
    }

    /* Return to the accounts. */
    Horde::applicationUrl('accounts.php', true)->redirect();

case 'save_account':
    if ($vars->get('submitbutton') == _("Delete this account")) {
        /* Redirect to the delete form. */
        $account_id = $vars->get('account_id');
        header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('account.php', true), array('account' => $account_id, 'actionID' => 'delete_account'), null, false));
        exit;
    }

    $form = new Fima_AccountForm($vars, $vars->get('account_id') ? sprintf(_("Edit: %s"), $vars->get('name')) : _("New Account"));
    if (!$form->validate($vars)) {
        break;
    }

    $form->getInfo($vars, $info);
    if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(sprintf(_("Access denied saving account to %s."), $share->get('name')), 'horde.error');
        Horde::applicationUrl('accounts.php', true)->redirect();
    }

    $storage = &Fima_Driver::singleton($ledger);
    $info['number_new'] = sprintf('%\'04d', $info['number_new']);

    /* Check for existing account width provided number. */
    if ($info['number'] != $info['number_new']) {
        $existingaccount = $storage->getAccountByNumber($info['number_new']);
        if (!is_a($existingaccount, 'PEAR_Error')) {
            $notification->push(sprintf(_("The account number %s is already used by the account %s."), $info['number_new'], trim($existingaccount['number'] . ' ' . $existingaccount['name'])), 'horde.error');
            break;
        } else {
            $notification->push(sprintf(_("The account including all postings was shifted from number %s to %s."), $info['number'], $info['number_new']), 'horde.message');
        }
    }
    
    /* Check account type. */
    if (($parent_number = Fima::getAccountParent($info['number_new'])) !== null) {
        $parent = $storage->getAccountByNumber($parent_number);
        if (!is_a($parent, 'PEAR_Error')) {
            if ($info['type'] != $parent['type']) {
                $info['type'] = $parent['type'];
                $notification->push(sprintf(_("The account type was set to %s."), Fima::getAccountTypes($info['type'])), 'horde.message');
            }
        }
    }

    /* If an account id is set, we're modifying an existing account.
     * Otherwise, we're adding a new account with the provided
     * attributes. */
    if (!empty($info['account_id'])) {
        $result = $storage->modifyAccount($info['account_id'],
                                          $info['number_new'],
                                          $info['type'],
                                          $info['name'],
                                          $info['eo'],
                                          $info['desc'],
                                          $info['closed']);
    } else {
        $result = $storage->addAccount($info['number_new'],
                                       $info['type'],
                                       $info['name'],
                                       $info['eo'],
                                       $info['desc'],
                                       $info['closed']);
    }

    /* Check our results. */
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was a problem saving the account: %s."), $result->getMessage()), 'horde.error');
    } else {
        $notification->push(sprintf(_("Saved %s."), trim($info['number_new'] . ' ' . $info['name'])), 'horde.success');
        /* Return to the accounts. */
        if ($vars->get('submitbutton') == _("Save and New")) {
            header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('account.php', true), array('account' => $vars->get('parent_id'), 'actionID' => 'add_account'), null, false));
            exit;
        }
        Horde::applicationUrl('accounts.php', true)->redirect();
    }

    break;

case 'delete_account':
    $account_id = $vars->get('account');
    if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
        $notification->push(_("Access denied deleting account."), 'horde.error');
    } else {
        $account = Fima::getAccount($account_id);
        if (!isset($account) || !isset($account['account_id'])) {
            $notification->push(_("Account not found."), 'horde.error');
        } else {
            $vars = new Horde_Variables($account);
            $vars->set('actionID', 'purge_account');
            $vars->set('dssubaccounts', array('type' => 'none', 'account' => $account_id));
            $vars->set('dspostings', array('type' => 'delete', 'account' => $account_id));
            $form = new Fima_AccountDeleteForm($vars, sprintf(_("Delete: %s"), trim($account['number'] . ' ' . $account['name'])), $share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT));
            break;
        }
    }

    /* Return to the accounts. */
    Horde::applicationUrl('accounts.php', true)->redirect();

case 'purge_account':
    if ($vars->get('submitbutton') == _("Edit this account")) {
        /* Redirect to the edit form. */
        $account_id = $vars->get('account_id');
        header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('account.php', true), array('account' => $account_id, 'actionID' => 'modify_account'), null, false));
        exit;
    }

    $form = new Fima_AccountDeleteForm($vars, sprintf(_("Delete: %s"), trim($vars->get('number') . ' ' . $vars->get('name'))));
    if (!$form->validate($vars)) {
        break;
    }

    $form->getInfo($vars, $info);
    if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
        $notification->push(sprintf(_("Access denied deleting account from %s."), $share->get('name')), 'horde.error');
        Horde::applicationUrl('accounts.php', true)->redirect();
    }

    $storage = &Fima_Driver::singleton($ledger);

    /* Delete the account. */
    $result = $storage->deleteAccount($info['account_id'], $info['dssubaccounts'], $info['dspostings']);

    /* Check our results. */
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was a problem deleting the account: %s."), $result->getMessage()), 'horde.error');
    } else {
        $notification->push(sprintf(_("Deleted %s."), trim($info['number_new'] . ' ' . $info['name'])), 'horde.success');
        /* Return to the accounts. */
        Horde::applicationUrl('accounts.php', true)->redirect();
    }

    break;

default:
    Horde::applicationUrl('accounts.php', true)->redirect();
}

$title = $form->getTitle();
require FIMA_TEMPLATES . '/common-header.inc';
require FIMA_TEMPLATES . '/menu.inc';
$form->renderActive();
require $registry->get('templates', 'horde') . '/common-footer.inc';
