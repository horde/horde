<?php
/**
 * Blacklist script.
 *
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
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
$bl_rule = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS)->findRule(Ingo_Storage::ACTION_BLACKLIST);

/* Prepare the view. */
$view = new Horde_View(array(
    'templatePath' => INGO_TEMPLATES . '/basic/blacklist'
));
$view->addHelper('Horde_Core_View_Helper_Help');
$view->addHelper('Horde_Core_View_Helper_Label');
$view->addHelper('FormTag');
$view->addHelper('Tag');
$view->addHelper('Text');

$view->blacklist = implode("\n", $blacklist->getBlacklist());
$view->disabled = !empty($bl_rule['disable']);
$view->flagonly = $flagonly;
$view->folder = $blacklist_folder;
$view->folderlist = $folder_list;
$view->formurl = Horde::url('blacklist.php');

$page_output->addScriptFile('blacklist.js');
$page_output->addInlineJsVars(array(
    'IngoBlacklist.filtersurl' => strval(Horde::url('filters.php', true)->setRaw(true))
));

$page_output->header(array(
    'title' => _("Blacklist Edit")
));
Ingo::status();
echo $view->render('blacklist');
$page_output->footer();
