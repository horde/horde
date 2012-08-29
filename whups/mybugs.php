<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('whups');

// Get refresh interval.
if ($r_time = $prefs->getValue('summary_refresh_time') &&
    !$browser->hasFeature('xmlhttpreq')) {
    $page_output->metaRefresh($r_time, Horde::url('mybugs.php'));
}

// Load layout from preferences for authenticated users, and a default
// block set for guests.
if (!$registry->isAuthenticated()) {
    $prefs->setValue('mybugs_layout', serialize(array(
        array(array('app' => 'whups', 'params' => array('type2' => 'whups_Block_Myqueries', 'params' => false), 'height' => 1, 'width' => 1)),
        array(array('app' => 'whups', 'params' => array('type2' => 'whups_Block_Queuesummary', 'params' => false), 'height' => 1, 'width' => 1)),
    )));
} elseif (!@unserialize($prefs->getValue('mybugs_layout'))) {
    $prefs->setValue('mybugs_layout', serialize(array(
        array(array('app' => 'whups', 'params' => array('type2' => 'whups_Block_Mytickets', 'params' => false), 'height' => 1, 'width' => 1)),
        array(array('app' => 'whups', 'params' => array('type2' => 'whups_Block_Myrequests', 'params' => false), 'height' => 1, 'width' => 1)),
        array(array('app' => 'whups', 'params' => array('type2' => 'whups_Block_Myqueries', 'params' => false), 'height' => 1, 'width' => 1))
    )));
}

$layout = new Horde_Core_Block_Layout_View(
    $injector->getInstance('Horde_Core_Factory_BlockCollection')->create(array('whups'), 'mybugs_layout')->getLayout(),
    Horde::url('mybugs_edit.php'),
    Horde::url('mybugs.php', true)
);
$layout_html = $layout->toHtml();

$menuBottom = '<div id="menuBottom"><a href="' . Horde::url('mybugs_edit.php') . '">' . _("Add Content") . '</a></div><div class="clear">&nbsp;</div>';
$page_output->header(array(
    'title' => sprintf(_("My %s"), $registry->get('name'))
));
require WHUPS_TEMPLATES . '/menu.inc';
echo $layout_html;
$page_output->footer();
