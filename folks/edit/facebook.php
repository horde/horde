<?php
/**
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

define('FOLKS_BASE', __DIR__ . '/..');
require_once FOLKS_BASE . '/lib/base.php';
require_once 'tabs.php';

$title = _("Facebook");

// Check FB installation
if (!$conf['facebook']['enabled']) {
    $notification->push(sprintf(_("Could not find authorization for %s to interact with your Facebook account."), $GLOBALS['registry']->get('name', 'horde')));
    Horde::url('user.php')->redirect();
}

// Load horde central block
try {
    $block = $registry->call('horde/blockContent', array('horde', 'fb_summary'));
} catch (Horde_Exception $e) {
    $notification->push($e);
    Horde::url('user.php')->redirect();
}

$page_output->header(array(
    'title' => $title
));
require FOLKS_TEMPLATES . '/menu.inc';
echo $tabs->render('facebook');
echo $block;
$page_output->footer();
