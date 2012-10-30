<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('hermes');

/* Determine View */
switch ($registry->getView()) {
case Horde_Registry::VIEW_DYNAMIC:
    if ($prefs->getValue('dynamic_view')) {
        $injector->getInstance('Hermes_Ajax')->init();
        require HERMES_TEMPLATES . '/dynamic/index.inc';
        echo $injector->getInstance('Hermes_View_Sidebar');
        $page_output->footer();
        exit;
    }
default:
    include HERMES_BASE . '/time.php';
    exit;
}
