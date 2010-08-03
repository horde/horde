<?php
/**
 * $Id: edit.php 974 2008-10-07 19:46:00Z duck $
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
require_once 'tabs.php';

$title = _("Facebook");

// Check FB installation
if (!$conf['facebook']['enabled']) {
    $notification->push(sprintf(_("Could not find authorization for %s to interact with your Facebook account."), $GLOBALS['registry']->get('name', 'horde')));
    Horde::applicationUrl('user.php')->redirect();
}

// Load horde central block
try {
    $block = $registry->call('horde/blockContent', array('horde', 'fb_summary'));
} catch (Horde_Exception $e) {
    $notification->push($e);
    Horde::applicationUrl('user.php')->redirect();
}

require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';
echo $tabs->render('facebook');
echo $block;
require $registry->get('templates', 'horde') . '/common-footer.inc';
