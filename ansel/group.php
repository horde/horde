<?php
/**
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Ben Chavet <ben@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

// check for grouping
$groupby = basename(Horde_Util::getFormData('groupby', $prefs->getValue('groupby')));

// check for pref update
$actionID = Horde_Util::getFormData('actionID');
if ($actionID == 'groupby' &&
    ($groupby == 'owner' || $groupby == 'category' || $groupby == 'none')) {
    $prefs->setValue('groupby', $groupby);
}

// If we aren't supplied with a page number, default to page 0.
$gbpage = Horde_Util::getFormData('gbpage', 0);
$groups_perpage = $prefs->getValue('groupsperpage');

switch ($groupby) {
case 'category':
    try {
        $num_groups = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->countCategories(Horde_Perms::SHOW);
    } catch (Ansel_Exception $e) {
        $notification->push($num_groups);
        $num_groups = 0;
        $groups = array();
    }
    if ($num_groups) {
        $groups = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->listCategories(Horde_Perms::SHOW,
                                                 $gbpage * $groups_perpage,
                                                 $groups_perpage);
    } else {
        $groups = array();
    }
    break;

case 'owner':
    try {
        if ($num_groups = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->shares->countOwners(Horde_Perms::SHOW, null, false)) {

            $groups = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->shares->listOwners(Horde_Perms::SHOW,
                                                         null,
                                                         false,
                                                         $gbpage * $groups_perpage,
                                                         $groups_perpage);
        } else {
            $groups = array();
        }
    } catch (Horde_Share_Exception $e) {
        $notification->push($e->getMessage());
        $num_groups = 0;
        $groups = array();
    }
    break;

default:
    header('Location: ' . Ansel::getUrlFor('view',
                                           array(
                                               'view' => 'List',
                                               'groupby' => $groupby
                                           ),
                                           true));
    exit;
}

// Set up pager.
$vars = Horde_Variables::getDefaultVariables();
$group_pager = new Horde_Ui_Pager('gbpage',
                                  $vars,
                                  array(
                                      'num' => $num_groups,
                                      'url' => 'group.php',
                                      'perpage' => $groups_perpage
                                  ));

$min = $gbpage * $groups_perpage;
$max = $min + $groups_perpage;
if ($max > $num_groups) {
    $max = $num_groups - $min;
}
$start = $min + 1;
$end = min($num_groups, $min + $groups_perpage);
$count = 0;
$groupby_links = array();
if ($groupby !== 'owner') {
    $groupby_links[] = Ansel::getUrlFor('group', array('actionID' => 'groupby', 'groupby' => 'owner'))->link() . _("owner") . '</a>';
} elseif ($groupby !== 'category') {
    $groupby_links[] = Ansel::getUrlFor('group', array('actionID' => 'groupby', 'groupby' => 'category'))->link() . _("category") . '</a>';
}
if ($groupby !== 'none') {
    $groupby_links[] = Ansel::getUrlFor('group', array('actionID' => 'groupby', 'groupby' => 'none'))->link() . _("none") . '</a>';
}

require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
require ANSEL_TEMPLATES . '/group/header.inc';
foreach ($groups as $group) {
    require ANSEL_TEMPLATES . '/group/' . $groupby . '.inc';
}
require ANSEL_TEMPLATES . '/group/footer.inc';
require ANSEL_TEMPLATES . '/group/pager.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
