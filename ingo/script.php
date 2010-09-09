<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ingo');

/* Redirect if script updating is not available. */
if (!$_SESSION['ingo']['script_generate']) {
    Horde::url('filters.php', true)->redirect();
}

$script = '';

/* Get the Ingo_Script:: backend. */
$scriptor = Ingo::loadIngoScript();

/* Generate the script. */
$script = $scriptor->generate();

/* Activate/deactivate script if requested.
   activateScript() does its own $notification->push() on error. */
$actionID = Horde_Util::getFormData('actionID');
switch ($actionID) {
case 'action_activate':
    if (!empty($script)) {
        Ingo::activateScript($script);
    }
    break;

case 'action_deactivate':
    Ingo::activateScript('', true);
    break;

case 'show_active':
    try {
        $script = Ingo::getScript();
    } catch (Ingo_Exception $e) {
        $notification->push($e);
        $script = '';
    }
    break;
}

$title = _("Filter Script Display");
$menu = Ingo::menu();
require INGO_TEMPLATES . '/common-header.inc';
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
require $registry->get('templates', 'horde') . '/common-footer.inc';
