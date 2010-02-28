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

$action = Horde_Util::getFormData('action');
$menu = Horde_Util::getFormData('menu');
$context = $_SESSION['shout']['context'];

$menus = $shout->storage->getMenus($context);

switch($action) {
case 'edit':
    if (!isset($menus[$menu])) {
        $notification->push(_("That menu does not exist."), 'horde.error');
        $action = 'list';
        break;
    }
    $menu = $menus[$menu];
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

require SHOUT_TEMPLATES . '/dialplan/' . $action . '.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';
