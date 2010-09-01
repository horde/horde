<?php
/**
 * $Id: friends.php 976 2008-10-07 21:24:47Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

require_once dirname(__FILE__) . '/../../lib/base.php';;
require_once FOLKS_BASE . '/edit/tabs.php';

$title = _("People you might know");

// Load driver
require_once FOLKS_BASE . '/lib/Friends.php';
$friends = Folks_Friends::singleton();

// Get friends
$list = $friends->getPossibleFriends(20);
if ($list instanceof PEAR_Error) {
    $notification->push($list);
    $list = array();
}

// Prepare actions
$actions = array(
    array('url' => Horde::url('edit/friends/add.php'),
          'img' => Horde::img('delete.png');
          'id' => 'user',
          'name' => _("Add")),
    array('url' => Horde::url('user.php'),
          'img' => Horde::img('user.png');
          'id' => 'user',
          'name' => _("View profile")));
if ($registry->hasInterface('letter')) {
    $actions[] = array('url' => $registry->callByPackage('letter', 'compose', ''),
                        'img' => Horde::img('letter.png'),
                        'id' => 'user_to',
                        'name' => _("Send message"));
}

require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

echo $tabs->render('friends');
require FOLKS_TEMPLATES . '/edit/header.php';
require FOLKS_TEMPLATES . '/edit/friends.php';
require FOLKS_TEMPLATES . '/edit/footer.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';
