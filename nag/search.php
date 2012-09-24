<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('nag');

$page_output->addInlineScript(array(
    '$("search_pattern")'
), true);

$page_output->header(array(
    'title' => _("Search")
));

// Editing existing SmartList?
$vars = Horde_Variables::getDefaultVariables();

if ($id = $vars->get('smart_id')) {
    $list = $nag_shares->getShare($id);
    $searchObj = unserialize($list->get('search'));
    $vars->set('smartlist_name', $list->get('name'));
    $searchObj->getVars($vars);
    $form = new Nag_Form_Search($vars, sprintf(_("Editing SmartList \"%s\""), htmlspecialchars($list->get('name'))));
} else {
    $form = new Nag_Form_Search($vars, _("Search"));
}

Nag::status();
$form->renderActive();
$page_output->footer();
