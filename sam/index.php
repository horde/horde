<?php
/*
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chris Bowlby <cbowlby@tenthpowertech.com>
 * @author Jan Schneider <jan@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('sam');

if ($conf['enable']['rules']) {
    require SAM_BASE . '/spam.php';
} else {
    require SAM_BASE . '/blacklist.php';
}
