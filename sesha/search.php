<?php
/**
 * This file is the basic display of the Inventory application for Horde,
 * Sesha. It should also be able to display search results and other useful
 * things.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 * Copyright 2004-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('sesha');

// Page variables.
$title = _("Search Inventory");
$actionId = Horde_Util::getFormData('actionId');

// Form creation.
$vars = Horde_Variables::getDefaultVariables();
$renderer = new Horde_Form_Renderer();
$form = new SearchForm($vars);
$vars->set('location', array(SESHA_SEARCH_NAME));

// Page display.
require $registry->get('templates', 'horde') . '/common-header.inc';
$form->renderActive($renderer, $vars, 'list.php', 'post');
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
require $registry->get('templates', 'horde') . '/common-footer.inc';
