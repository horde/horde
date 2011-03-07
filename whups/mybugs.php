<?php
/**
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('whups');

// @TODO: remove this when there are blocks useful to guests
// available.
if (!$GLOBALS['registry']->getAuth()) {
    require WHUPS_BASE . '/search.php';
    exit;
}

// Get refresh interval.
if ($r_time = $prefs->getValue('summary_refresh_time')) {
    if ($browser->hasFeature('xmlhttpreq')) {
        Horde::addScriptFile('prototype.js', 'horde', true);
    } else {
        Horde::metaRefresh($r_time, Horde::url('mybugs.php'));
    }
}

// Load layout from preferences for authenticated users, and a default
// block set for guests.
$mybugs_layout = @unserialize($prefs->getValue('mybugs_layout'));
if (!$mybugs_layout) {
    if ($registry->isAuthenticated()) {
        $mybugs_layout = array(
            array(array('app' => 'whups', 'params' => array('type' => 'mytickets', 'params' => false), 'height' => 1, 'width' => 1)),
            array(array('app' => 'whups', 'params' => array('type' => 'myrequests', 'params' => false), 'height' => 1, 'width' => 1)),
            array(array('app' => 'whups', 'params' => array('type' => 'myqueries', 'params' => false), 'height' => 1, 'width' => 1)));
        $prefs->setValue('mybugs_layout', serialize($mybugs_layout));
    } else {
        // @TODO: show some blocks that are useful to guests.
        $mybugs_layout = array();
    }
}
$layout = new Horde_Core_Block_Layout_View(
    $mybugs_layout,
    Horde::url('mybugs_edit.php'),
    Horde::url('mybugs.php', true)
);
$layout_html = $layout->toHtml();

$title = sprintf(_("My %s"), $registry->get('name'));
$menuBottom = '<div id="menuBottom"><a href="' . Horde::url('mybugs_edit.php') . '">' . _("Add Content") . '</a></div><div class="clear">&nbsp;</div>';
require $registry->get('templates', 'horde') . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';
echo $layout_html;
require $registry->get('templates', 'horde') . '/common-footer.inc';
