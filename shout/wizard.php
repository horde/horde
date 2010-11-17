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
    $curaccount = $GLOBALS['session']->get('shout', 'curaccount_code');

    // Only continue if there is an assigned phone number
    $numbers = $shout->storage->getNumbers($curaccount);
    if (empty($numbers)) {
        throw new Shout_Exception("No valid numbers on this account.");
    }
    // Grab the first available number
    $number = reset($numbers);
    $number = $number['number'];

    // Only continue if there is no existing "Main Menu"
    $menus = $shout->storage->getMenus($curaccount);

    if (!empty($menus) && !empty($menus[Shout::MAIN_MENU])) {
        Horde::url('dialplan.php', true)->redirect();
    }

    // Create the default recording for the main menu
    try {
        $recording = $shout->storage->getRecordingByName($curaccount,
                                                         Shout::MAIN_RECORDING);
    } catch (Shout_Exception $e) {
        $shout->storage->addRecording($curaccount, Shout::MAIN_RECORDING);
        $recording = $shout->storage->getRecordingByName($curaccount,
                                                         Shout::MAIN_RECORDING);
    }

    // Create a default main menu
    $details = array(
        'name' => Shout::MAIN_MENU,
        'description' => _("Main menu: what your callers will hear."),
        'recording_id' => $recording['id']
    );
    $shout->dialplan->saveMenuInfo($curaccount, $details);

    // Associate this menu with the first number.
    // FIXME: This could be disruptive.
    $shout->storage->saveNumber($number, $curaccount, Shout::MAIN_MENU);

    // Populate the default option, granting the ability to log into the admin
    // section.
    $shout->dialplan->saveMenuAction($curaccount, Shout::MAIN_MENU,
                                     'star', 'admin_login', array());
    $extensions = $shout->extensions->getExtensions($curaccount);
} catch (Exception $e) {
    print_r($e);
    $notification->push($e);
}

Horde::addScriptFile('scriptaculous.js', 'horde');
Horde::addScriptFile('stripe.js', 'horde');

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

require SHOUT_TEMPLATES . '/wizard.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';
