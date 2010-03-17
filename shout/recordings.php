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
$curaccount = $_SESSION['shout']['curaccount'];
$recordings = Shout::getRecordings($curaccount);

switch($action) {
case 'add':
    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('account', $curaccount);
    $Form = new MenuForm($vars);

    if ($Form->isSubmitted() && $Form->validate($vars, true)) {
        // Form is Valid and Submitted
        try {
            $Form->execute();
            $notification->push(_("Menu added."),
                                  'horde.success');
            $menus = $shout->storage->getMenus($curaccount);
            $action = 'list';
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
    if (!isset($menus[$menu])) {
        $notification->push(_("That menu does not exist."), 'horde.error');
        $action = 'list';
        break;
    }
    $menu = $menus[$menu];
    try {
        $destinations = $shout->extensions->getExtensions($curaccount);
    } catch (Exception $e) {
        $notification->push(_("Problem getting destination information."));
    }
    break;
case 'list':
default:
    $action = 'list';
    break;
}

Horde::addScriptFile('stripe.js', 'horde');
Horde::addScriptFile('prototype.js', 'horde');

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

require SHOUT_TEMPLATES . '/recordings/' . $action . '.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';
