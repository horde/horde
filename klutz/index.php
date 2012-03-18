<?php
/**
 * Klutz index script.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */
require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('klutz');
require __DIR__ . '/comics.php';
