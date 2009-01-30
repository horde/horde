<?php
/**
 * $Id: blacklist.php 1234 2009-01-28 18:44:02Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

define('FOLKS_BASE', dirname(__FILE__) . '/..');
require_once FOLKS_BASE . '/lib/base.php';
require_once FOLKS_BASE . '/lib/Forms/AddFriend.php';
require_once 'tabs.php';

$title = _("Blacklist");
$remove_url = Util::addParameter(Horde::applicationUrl('edit/blacklist.php'), 'user', null);
$remove_img = Horde::img('delete.png', '', '', $registry->getImageDir('horde'));
$profile_img = Horde::img('user.png', '', '', $registry->getImageDir('horde'));

// Load driver
require_once FOLKS_BASE . '/lib/Friends.php';
$friends = Folks_Friends::singleton();

// Perform action
$user = Util::getGet('user');
if ($user) {
    if ($friends->isBlacklisted($user)) {
        $result = $friends->removeBlacklisted($user);
        if ($result instanceof PEAR_Error) {
            $notification->push($result);
        } else {
            $notification->push(sprintf(_("User \"%s\" was removed from your blacklist."), $user), 'horde.success');
        }
    } else {
        $result = $friends->addBlacklisted($user);
        if ($result instanceof PEAR_Error) {
            $notification->push($result);
        } else {
            $notification->push(sprintf(_("User \"%s\" was added to your blacklist."), $user), 'horde.success');
        }
    }
}

// Get blacklist
$blacklist = $friends->getBlacklist();
if ($blacklist instanceof PEAR_Error) {
    $notification->push($blacklist);
    $blacklist = array();
}

$form = new Folks_AddFriend_Form($vars, _("Add or remove user"), 'blacklist');

Horde::addScriptFile('tables.js', 'horde', true);

require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

echo $tabs->render('blacklist');
require FOLKS_TEMPLATES . '/edit/blacklist.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';