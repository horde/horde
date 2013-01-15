<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once __DIR__ . '/lib/Application.php';
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

$topbar = $injector->getInstance('Horde_View_Topbar');
$topbar->search = true;
$topbar->searchAction = new Horde_Url('ticket');
$topbar->searchLabel =  $session->get('whups', 'search') ?: _("Ticket #Id");
$topbar->searchIcon = Horde_Themes::img('search-topbar.png');

$page_output->header(array(
    'title' => sprintf(_("My %s :: Add Content"), $registry->get('name'))
));
require WHUPS_TEMPLATES . '/menu.inc';
require $registry->get('templates', 'horde') . '/portal/edit.inc';
$page_output->footer();
