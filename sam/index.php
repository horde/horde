<?php
/*
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chris Bowlby <cbowlby@tenthpowertech.com>
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('sam');

if ($conf['enable']['rules']) {
    require SAM_BASE . '/spam.php';
} else {
    require SAM_BASE . '/blacklist.php';
}
