<?php
/**
 * Blacklist script.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ingo');

/* Redirect if blacklist is not available. */
if (!in_array(Ingo_Storage::ACTION_BLACKLIST, $session->get('ingo', 'script_categories'))) {
    $notification->push(_("Blacklist is not supported in the current filtering driver."), 'horde.error');
    Horde::url('filters.php', true)->redirect();
}

/* Get the backend. */
$scriptor = Ingo::loadIngoScript();

/* Get the blacklist object. */
try {
    $blacklist = $ingo_storage->retrieve(Ingo_Storage::ACTION_BLACKLIST);
} catch (Ingo_Exception $e) {
    $notification->push($e);
    $blacklist = new Ingo_Storage_Blacklist();
}
$folder = $blacklist_folder = null;

/* Perform requested actions. */
$vars = Horde_Variables::getDefaultVariables();
switch ($vars->actionID) {
case 'rule_update':
    switch ($vars->action) {
    case 'delete':
        $folder = '';
        break;

    case 'mark':
        $folder = Ingo::BLACKLIST_MARKER;
        break;

    case 'folder':
        $folder = Ingo::validateFolder($vars, 'actionvalue');
        break;
    }

    if ($folder == Ingo::BLACKLIST_MARKER &&
        (!$scriptor ||
         !in_array(Ingo_Storage::ACTION_FLAGONLY, $scriptor->availableActions()))) {

        $notification->push("Not supported by this script generator.", 'horde.error');
    } else {
        try {
            $blacklist->setBlacklist($vars->blacklist);
            $blacklist->setBlacklistFolder($folder);
            $ingo_storage->store($blacklist);
            $notification->push(_("Changes saved."), 'horde.success');
            if ($prefs->getValue('auto_update')) {
                /* This does its own $notification->push() on error: */
                Ingo::updateScript();
            }
        } catch (Ingo_Exception $e) {
            $notification->push($e->getMessage(), $e->getCode());
        }
        /* Update the timestamp for the rules. */
        $session->set('ingo', 'change', time());
    }

    break;
}

/* Create the folder listing. */
if (!isset($blacklist_folder)) {
    $blacklist_folder = $blacklist->getBlacklistFolder();
}
$folder_list = Ingo::flistSelect($blacklist_folder, 'filters', 'actionvalue');

/* Get the blacklist rule. */
$filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
$bl_rule = $filters->findRule(Ingo_Storage::ACTION_BLACKLIST);

Horde::addScriptFile('blacklist.js', 'ingo');

$menu = Ingo::menu();
$title = _("Blacklist Edit");
require $registry->get('templates', 'horde') . '/common-header.inc';
echo $menu;
Ingo::status();
require INGO_TEMPLATES . '/blacklist/blacklist.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
