<?php
/**
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('nag');

Horde::addInlineScript(array(
    '$("search_pattern")'
), 'dom');

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
