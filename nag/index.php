<?php
/**
 * Nag index script.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('nag');
if ($session->get('horde', 'mode') == 'smartmobile' && Horde::ajaxAvailable()) {
    require dirname(__FILE__) . '/mobile.php';
} else {
    require dirname(__FILE__) . '/list.php';
}