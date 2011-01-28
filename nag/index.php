<?php
/**
 * Nag index script.
 *
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('nag');
if ($browser->isMobile()) {
    require dirname(__FILE__) . '/mobile.php';
} else {
    require dirname(__FILE__) . '/list.php';
}