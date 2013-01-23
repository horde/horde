<?php
/**
 * This file is the basic display of the Inventory application for Horde,
 * Sesha. It should also be able to display search results and other useful
 * things.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 * Copyright 2004-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('sesha');

$topbar = $injector->getInstance('Horde_View_Topbar');
$topbar->search = true;
$topbar->searchAction = new Horde_Url('list.php');
$topbar->searchLabel = _("Stock Id");
$topbar->searchIcon = Horde_Themes::img('search-topbar.png');

// Page variables.
$title = _("Search Inventory");
$actionId = Horde_Util::getFormData('actionId');

// Form creation.
$vars = Horde_Variables::getDefaultVariables();
$renderer = new Horde_Form_Renderer();
$form = new Sesha_Form_Search($vars);
$vars->set('location', array(Sesha::SEARCH_NAME));

// Page display.
$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
$form->renderActive($renderer, $vars, Horde::url('list.php'), 'post');
$page_output->footer();
