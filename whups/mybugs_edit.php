<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('whups');

// Instantiate the blocks objects.
$blocks = Horde_Block_Collection::singleton(array('whups'));
$layout = Horde_Block_Layout_Manager::singleton('mybugs', $blocks, @unserialize($prefs->getValue('mybugs_layout')));

// Handle requested actions.
$layout->handle(Horde_Util::getFormData('action'),
                (int)Horde_Util::getFormData('row'),
                (int)Horde_Util::getFormData('col'),
                Horde_Util::getFormData('url'));
if ($layout->updated()) {
    $prefs->setValue('mybugs_layout', $layout->serialize());
}

$title = sprintf(_("My %s :: Add Content"), $registry->get('name'));
require $registry->get('templates', 'horde') . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';
require $registry->get('templates', 'horde') . '/portal/edit.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
