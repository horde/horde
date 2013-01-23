<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ingo');

/* Redirect if script updating is not available. */
$ingo_script = $injector->getInstance('Ingo_Script');
if (!$ingo_script->hasFeature('script_file')) {
    Horde::url('filters.php', true)->redirect();
}

/* Generate the script. */
$script = $ingo_script->generate();
$additional = $ingo_script->additionalScripts();

/* Activate/deactivate script if requested. */
$actionID = Horde_Util::getFormData('actionID');
switch ($actionID) {
case 'action_activate':
    if (!empty($script)) {
        try {
            Ingo::activateScript($script, false, $additional);
        } catch (Ingo_Exception $e) {
            $notification->push($e);
        }
    }
    break;

case 'action_deactivate':
    try {
        Ingo::activateScript('', true, $additional);
    } catch (Ingo_Exception $e) {
        $notification->push($e);
    }
    break;

case 'show_active':
    try {
        $script = $injector->getInstance('Ingo_Factory_Transport')
            ->create($session->get('ingo', 'backend/transport'))
            ->getScript();
    } catch (Ingo_Exception $e) {
        $notification->push($e);
        $script = '';
    }
    break;
}

/* Prepare the view. */
$view = new Horde_View(array(
    'templatePath' => INGO_TEMPLATES . '/basic/script'
));
$view->addHelper('Text');

$view->scriptexists = !empty($script);
$view->scripturl = Horde::url('script.php');
$view->showactivate = ($actionID != 'show_active');
if ($view->scriptexists) {
    $view->lines = preg_split('(\r\n|\n|\r)', $script);
}

$page_output->header(array(
    'title' => _("Filter Script Display")
));
Ingo::status();
echo $view->render('script');
$page_output->footer();
