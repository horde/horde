<?php
/**
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Jon Parise <jon@horde.org>
 * @package Mnemo
 */
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

Horde::addInlineScript(array(
    '$("search_pattern").focus()'
), 'dom');

if ($prefs->getValue('show_panel')) {
    $bodyClass = 'rightPanel';
}

$title = _("Search");
require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify();
require MNEMO_TEMPLATES . '/search/search.inc';
require MNEMO_TEMPLATES . '/panel.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
