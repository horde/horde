<?php
/**
 * Whitelist script.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 * @author Brent J. Nordquist <bjn@horde.org>
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ingo');

/* Redirect if whitelist not available. */
if (!in_array(Ingo_Storage::ACTION_WHITELIST, $session->get('ingo', 'script_categories'))) {
    $notification->push(_("Whitelist is not supported in the current filtering driver."), 'horde.error');
    Horde::url('filters.php', true)->redirect();
}

$whitelist = $ingo_storage->retrieve(Ingo_Storage::ACTION_WHITELIST);

/* Perform requested actions. */
switch (Horde_Util::getFormData('actionID')) {
case 'rule_update':
    try {
        $whitelist->setWhitelist(Horde_Util::getFormData('whitelist'));
        $ingo_storage->store($whitelist);
        $notification->push(_("Changes saved."), 'horde.success');
        if ($prefs->getValue('auto_update')) {
            /* This does its own $notification->push() on error: */
            Ingo::updateScript();
        }

        /* Update the timestamp for the rules. */
        $session->set('ingo', 'change', time());
    } catch (Ingo_Exception $e) {
        $notification->push($e);
    }
    break;
}

/* Get the whitelist rule. */
$filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
$wl_rule = $filters->findRule(Ingo_Storage::ACTION_WHITELIST);

$title = _("Whitelist Edit");
$menu = Ingo::menu();
require $registry->get('templates', 'horde') . '/common-header.inc';
echo $menu;
Ingo::status();
require INGO_TEMPLATES . '/whitelist/whitelist.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
