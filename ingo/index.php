<?php
/**
 * Index script.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ingo');

switch ($registry->getView()) {
case $registry::VIEW_SMARTMOBILE:
    Horde::url('smartmobile.php')->redirect();
    break;

default:
    require __DIR__ . '/filters.php';
    break;
}
