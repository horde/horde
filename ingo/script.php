<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ingo');

/* Redirect if script updating is not available. */
if (!$session->get('ingo', 'script_generate')) {
    Horde::url('filters.php', true)->redirect();
}

/* Generate the script. */
$ingo_script = $injector->getInstance('Ingo_Script');
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
        $script = $injector->getInstance('Ingo_Transport')->getScript();
    } catch (Ingo_Exception $e) {
        $notification->push($e);
        $script = '';
    }
    break;
}

$menu = Ingo::menu();
$page_output->header(array(
    'title' => _("Filter Script Display")
));
echo $menu;
Ingo::status();
require INGO_TEMPLATES . '/script/header.inc';
if (!empty($script)) {
    require INGO_TEMPLATES . '/script/activate.inc';
}
require INGO_TEMPLATES . '/script/script.inc';
if (!empty($script)) {
    $lines = preg_split('(\r\n|\n|\r)', $script);
    $i = 0;
    foreach ($lines as $line) {
        printf("%3d: %s\n", ++$i, htmlspecialchars($line));
    }
} else {
    echo '[' . _("No script generated.") . ']';
}

require INGO_TEMPLATES . '/script/footer.inc';
$page_output->footer();
