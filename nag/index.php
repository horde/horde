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

switch ($registry->getView()) {
case $registry::VIEW_SMARTMOBILE:
    Horde::url('smartmobile.php')->setAnchor('nag-list')->redirect();
    break;

default:
    require __DIR__ . '/list.php';
    break;
}
