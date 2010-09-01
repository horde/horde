<?php
/**
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('FIMA_BASE', dirname(dirname(__FILE__)));
require_once FIMA_BASE . '/lib/base.php';
require_once FIMA_BASE . '/lib/Forms/CreateLedger.php';

// Exit if this isn't an authenticated user or if the user can't
// create new task lists (default share is locked).
if (!$GLOBALS['registry']->getAuth() || $prefs->isLocked('active_ledger')) {
    Horde::url('postings.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Fima_CreateLedgerForm($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } else {
        $notification->push(sprintf(_("The ledger \"%s\" has been created."), $vars->get('name')), 'horde.success');
    }

    Horde::url('ledgers/', true)->redirect();
}

$title = $form->getTitle();
require FIMA_TEMPLATES . '/common-header.inc';
require FIMA_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'create.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
