<?php
/**
 * Copyright 2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 */
require_once dirname(__FILE__) . '/lib/Application.php';
$shout = Horde_Registry::appInit('shout');

require_once SHOUT_BASE . '/lib/Forms/ExtensionForm.php';

try {
    // Only continue if there is no existing "Main Menu"
    $curaccount = $_SESSION['shout']['curaccount'];
    $menus = $shout->storage->getMenus($curaccount['code']);

    if (!empty($menus) && !empty($menus[Shout::MAIN_MENU])) {
        header('Location: ' . Horde::applicationUrl('dialplan.php', true));
        exit;
    }

    // Create the default recording for the main menu
    try {
        $recording = $shout->storage->getRecordingByName($curaccount['code'],
                                                         Shout::MAIN_RECORDING);
    } catch (Shout_Exception $e) {
        $shout->storage->addRecording($curaccount['code'], Shout::MAIN_RECORDING);
        $recording = $shout->storage->getRecordingByName($curaccount['code'],
                                                         Shout::MAIN_RECORDING);
    }

    // Create a default main menu
    $details = array(
        'name' => Shout::MAIN_MENU,
        'description' => _("Main menu: what your callers will hear."),
        'recording_id' => $recording['id']
    );
    $shout->dialplan->saveMenuInfo($curaccount['code'], $details);
    // Populate the default option, granting the ability to log into the admin
    // section.
    $shout->dialplan->saveMenuAction($curaccount['code'], Shout::MAIN_MENU,
                                     'star', 'admin_login', array());

    $vars = Horde_Variables::getDefaultVariables();
    $extensionform = new ExtensionDetailsForm($vars);
} catch (Exception $e) {
    $notification->push($e);
}

Horde::addScriptFile('prototype.js', 'horde');

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

require SHOUT_TEMPLATES . '/wizard.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';
