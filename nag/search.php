<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('nag');

$injector->getInstance('Horde_PageOutput')->addInlineScript(array(
    '$("search_pattern")'
), true);

if ($prefs->getValue('show_panel')) {
    $bodyClass = 'rightPanel';
}
$title = _("Search");

require $registry->get('templates', 'horde') . '/common-header.inc';
echo Nag::menu();
Nag::status();
require NAG_TEMPLATES . '/search/search.inc';
require NAG_TEMPLATES . '/panel.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
