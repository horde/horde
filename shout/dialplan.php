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

require_once SHOUT_BASE . '/lib/Forms/MenuForm.php';

$action = Horde_Util::getFormData('action');
$menu = Horde_Util::getFormData('menu');
$curaccount = $_SESSION['shout']['curaccount'];

$menus = $shout->storage->getMenus($curaccount['code']);
if (empty($menus)) {
    Horde::applicationUrl('wizard.php', true)->redirect();
}

switch($action) {
case 'add':
    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('account', $curaccount['code']);
    $Form = new MenuForm($vars);

    if ($Form->isSubmitted() && $Form->validate($vars, true)) {
        // Form is Valid and Submitted
        try {
            $Form->execute();
            $notification->push(_("Menu added."),
                                  'horde.success');
            $menus = $shout->storage->getMenus($curaccount['code']);
            $action = 'edit';

        } catch (Exception $e) {
            $notification->push($e);
        }
        break;
    } elseif ($Form->isSubmitted()) {
        $notification->push(_("Problem processing the form.  Please check below and try again."), 'horde.warning');
    }

    // Create a new add form
    $vars = new Horde_Variables();
    $vars->set('action', $action);
    //$Form = new MenuForm($vars);
    break;

case 'edit':
default:
    $action = 'edit';
    break;
}

// Check again explicitly for edit as we may have converted to an edit
// after a successful add.
if ($action == 'edit') {
    try {
        $destinations = $shout->extensions->getExtensions($curaccount['code']);
        $conferences = $shout->storage->getConferences($curaccount['code']);
        $recordings = $shout->storage->getRecordings($curaccount['code']);
        // If any of these are empty, we need to coerce them to null.
        // Otherwise we end up with a Prototype.js $H([]) (Hash of an empty
        // Array) which causes confusion inside the library.
        if (empty($destinations)) {
            $destinations = null;
        }
        if (empty($conferences)) {
            $conferences = null;
        }
        if (empty($recordings)) {
            $recordings = null;
        }
    } catch (Exception $e) {
        Horde::logMessage($e, 'ERR');
        $notification->push(_("Problem getting menu information."));
    }
}

Horde::addScriptFile('stripe.js', 'horde');
Horde::addScriptFile('prototype.js', 'horde');
Horde::addScriptFile('scriptaculous.js', 'horde');

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

require SHOUT_TEMPLATES . '/dialplan/' . $action . '.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';
