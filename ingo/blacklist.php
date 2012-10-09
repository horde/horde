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

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ingo');

/* Redirect if blacklist is not available. */
if (!in_array(Ingo_Storage::ACTION_BLACKLIST, $session->get('ingo', 'script_categories'))) {
    $notification->push(_("Blacklist is not supported in the current filtering driver."), 'horde.error');
    Horde::url('filters.php', true)->redirect();
}

$ingo_script = $injector->getInstance('Ingo_Script');
$ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
$folder = $blacklist_folder = null;

$flagonly = ($ingo_script && in_array(Ingo_Storage::ACTION_FLAGONLY, $ingo_script->availableActions()));

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

    if (!$flagonly && ($folder == Ingo::BLACKLIST_MARKER)) {
        $notification->push("Not supported by this script generator.", 'horde.error');
    } else {
        try {
            $blacklist = Ingo::updateListFilter($vars->blacklist, Ingo_Storage::ACTION_BLACKLIST);
            $blacklist->setBlacklistFolder($folder);
            $ingo_storage->store($blacklist);
            $notification->push(_("Changes saved."), 'horde.success');
            if ($prefs->getValue('auto_update')) {
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

/* Get the blacklist object. */
if (!isset($blacklist)) {
    try {
        $blacklist = $ingo_storage->retrieve(Ingo_Storage::ACTION_BLACKLIST);
    } catch (Ingo_Exception $e) {
        $notification->push($e);
        $blacklist = new Ingo_Storage_Blacklist();
    }
}

/* Create the folder listing. */
if (!isset($blacklist_folder)) {
    $blacklist_folder = $blacklist->getBlacklistFolder();
}
$folder_list = Ingo::flistSelect($blacklist_folder, 'actionvalue');

/* Get the blacklist rule. */
$filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
$bl_rule = $filters->findRule(Ingo_Storage::ACTION_BLACKLIST);

$page_output->addScriptFile('blacklist.js');

$menu = Ingo::menu();
$page_output->header(array(
    'title' => _("Blacklist Edit")
));
echo $menu;
Ingo::status();
require INGO_TEMPLATES . '/blacklist/blacklist.inc';
$page_output->footer();
