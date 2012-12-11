<?php
/**
 * Whitelist script.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Brent J. Nordquist <bjn@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ingo');

$vars = $injector->getInstance('Horde_Variables');

/* Redirect if whitelist not available. */
if (!in_array(Ingo_Storage::ACTION_WHITELIST, $session->get('ingo', 'script_categories'))) {
    $notification->push(_("Whitelist is not supported in the current filtering driver."), 'horde.error');
    Horde::url('filters.php', true)->redirect();
}

$ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
$whitelist = $ingo_storage->retrieve(Ingo_Storage::ACTION_WHITELIST);

/* Perform requested actions. */
switch ($vars->actionID) {
case 'rule_update':
    try {
        Ingo::updateListFilter($vars->whitelist, Ingo_Storage::ACTION_WHITELIST);
        $notification->push(_("Changes saved."), 'horde.success');
        if ($prefs->getValue('auto_update')) {
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
$wl_rule = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS)->findRule(Ingo_Storage::ACTION_WHITELIST);

/* Prepare the view. */
$view = new Horde_View(array(
    'templatePath' => INGO_TEMPLATES . '/basic/whitelist'
));
$view->addHelper('Horde_Core_View_Helper_Help');
$view->addHelper('Horde_Core_View_Helper_Label');
$view->addHelper('Text');

$view->disabled = !empty($wl_rule['disable']);
$view->formurl = Horde::url('whitelist.php');
$view->whitelist = implode("\n", $whitelist->getWhitelist());

$page_output->addScriptFile('whitelist.js');
$page_output->addInlineJsVars(array(
    'IngoWhitelist.filtersurl' => strval(Horde::url('filters.php', true)->setRaw(true))
));

$page_output->header(array(
    'title' => _("Whitelist Edit")
));
Ingo::status();
echo $view->render('whitelist');
$page_output->footer();
