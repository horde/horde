<?php
/**
 * Nag index script.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */
require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('nag');

if ($registry->getView() == Horde_Registry::VIEW_SMARTMOBILE) {
    require __DIR__ . '/smartmobile.php';
} else {
    require __DIR__ . '/list.php';
}
