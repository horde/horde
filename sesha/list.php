<?php
/**
 * This file is the classic list view of the Inventory application for Horde,
 * Sesha. It should also be able to display search results and other useful
 * things.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
$sesha = Horde_Registry::appInit('sesha');

/* Init some form vars. This is about the search field in the topbar */
$vars = Horde_Variables::getDefaultVariables();

if ($vars->searchfield || $vars->location) {
    $vars->criteria = $vars->searchfield;
    $vars->location = array(Sesha::SEARCH_ID, Sesha::SEARCH_NAME);
}

$session->set('sesha', 'search', strval($vars->searchfield));

$topbar = $injector->getInstance('Horde_View_Topbar');
$topbar->search = true;
$topbar->searchAction = new Horde_Url('list.php');
$topbar->searchLabel =  $session->get('sesha', 'search') ?: _("Stock Id");
$topbar->searchIcon = Horde_Themes::img('search-topbar.png');

/* While switching from Horde_Template to Horde_View, try to leave only lines which strictly need to be in this file */
// Start page display.

if (Horde_Util::getFormData('criteria')) {
    $sesha->highlight = 'sesha-search';
}
$view = new Sesha_View_List(array('templatePath'    => SESHA_TEMPLATES . '/view/',
                                'selectedCategories' => array(Horde_Util::getFormData('category_id')),
                                'sortDir'           => Horde_Util::getFormData('sortdir'),
                                'sortBy'            => Horde_Util::getFormData('sortby'),
                                'propertyIds'       => @unserialize($prefs->getValue('list_properties')),
                                'what'              => $vars->criteria,
                                'exact'             => Horde_Util::getFormData('exact'),
                                'loc'               => $vars->location
                            )
                            );
$page_output->addScriptFile('prototype.js', 'horde');
$page_output->addScriptFile('tables.js', 'horde');
$page_output->header(array(
    'title' => $view->title
));
#require SESHA_TEMPLATES . '/menu.inc';
echo $view->render('list.php');
$page_output->footer();

