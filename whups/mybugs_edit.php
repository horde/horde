<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
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
$blocks = $injector->getInstance('Horde_Core_Factory_BlockCollection')->create(array('whups'), 'mybugs_layout');
$layout = $blocks->getLayoutManager();

// Handle requested actions.
$layout->handle(Horde_Util::getFormData('action'),
                (int)Horde_Util::getFormData('row'),
                (int)Horde_Util::getFormData('col'));
if ($layout->updated()) {
    $prefs->setValue('mybugs_layout', $layout->serialize());
    if (Horde_Util::getFormData('url')) {
        $url = new Horde_Url(Horde_Util::getFormData('url'));
        $url->unique()->redirect();
    }
}

$title = sprintf(_("My %s :: Add Content"), $registry->get('name'));
require $registry->get('templates', 'horde') . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';
require $registry->get('templates', 'horde') . '/portal/edit.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
