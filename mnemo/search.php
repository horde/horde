<?php
/**
 * $Horde: mnemo/search.php,v 1.19 2009/01/06 18:01:15 jan Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Jon Parise <jon@horde.org>
 * @since   Mnemo 1.0
 * @package Mnemo
 */

@define('MNEMO_BASE', dirname(__FILE__));
require_once MNEMO_BASE . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

$title = _("Search");
$notification->push('document.getElementById(\'search_pattern\').focus();', 'javascript');
Horde::addScriptFile('prototype.js', 'horde', true);
require MNEMO_TEMPLATES . '/common-header.inc';
require MNEMO_TEMPLATES . '/menu.inc';
$notification->notify();
require MNEMO_TEMPLATES . '/search/search.inc';
require MNEMO_TEMPLATES . '/panel.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
