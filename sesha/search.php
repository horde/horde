<?php
/**
 * This file is the basic display of the Inventory application for Horde,
 * Sesha. It should also be able to display search results and other useful
 * things.
 *
 * $Horde: sesha/search.php,v 1.14 2009/06/10 17:33:42 slusarz Exp $
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('SESHA_BASE', dirname(__FILE__));
require_once SESHA_BASE . '/lib/base.php';
require_once SESHA_BASE . '/lib/Forms/Search.php';

// Page variables.
$title = _("Search Inventory");
$actionId = Horde_Util::getFormData('actionId');

// Form creation.
$vars = Horde_Variables::getDefaultVariables();
$renderer = new Horde_Form_Renderer();
$form = new SearchForm($vars);
$vars->set('location', array(SESHA_SEARCH_NAME));

// Page display.
require_once SESHA_TEMPLATES . '/common-header.inc';
require_once SESHA_TEMPLATES . '/menu.inc';
$form->renderActive($renderer, $vars, 'list.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
