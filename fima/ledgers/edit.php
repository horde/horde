<?php
/**
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('FIMA_BASE', dirname(dirname(__FILE__)));
require_once FIMA_BASE . '/lib/base.php';
require_once FIMA_BASE . '/lib/Forms/EditLedger.php';

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    Horde::applicationUrl('postings.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$ledger = $fima_shares->getShare($vars->get('l'));
if (is_a($ledger, 'PEAR_Error')) {
    $notification->push($ledger, 'horde.error');
    Horde::applicationUrl('ledgers/', true)->redirect();
}
if ($ledger->get('owner') != $GLOBALS['registry']->getAuth()) {
    $notification->push(_("You are not allowed to change this ledger."), 'horde.error');
    Horde::applicationUrl('ledgers/', true)->redirect();
}
$form = new Fima_EditLedgerForm($vars, $ledger);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $original_name = $ledger->get('name');
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } else {
        if ($ledger->get('name') != $original_name) {
            $notification->push(sprintf(_("The ledger \"%s\" has been renamed to \"%s\"."), $original_name, $ledger->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The ledger \"%s\" has been saved."), $original_name), 'horde.success');
        }
    }

    Horde::applicationUrl('ledgers/', true)->redirect();
}

$vars->set('name', $ledger->get('name'));
$vars->set('description', $ledger->get('desc'));
$title = $form->getTitle();
require FIMA_TEMPLATES . '/common-header.inc';
require FIMA_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'edit.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
