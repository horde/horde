<?php
/**
 * Copyright 1999-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

$blocks = $injector->getInstance('Horde_Core_Factory_BlockCollection')->create();
$layout = $blocks->getLayoutManager();
$vars = $injector->getInstance('Horde_Variables');

// Handle requested actions.
$layout->handle($vars->action, intval($vars->row), intval($vars->col));

if ($layout->updated()) {
    $prefs->setValue('portal_layout', $layout->serialize());
    if ($vars->url) {
        $url = new Horde_Url($vars->url);
        $url->unique()->redirect();
    }
}

$page_output->sidebar = false;

$page_output->header(array(
    'title' => _("My Portal Layout")
));
$notification->notify(array('listeners' => 'status'));
require HORDE_TEMPLATES . '/portal/edit.inc';
$page_output->footer();
