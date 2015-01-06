<?php
/**
 * This is an inventory application written for the Horde framework.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 * @author Ralf Lang <lang@b1-systems.de>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('sesha');

require basename($prefs->getValue('sesha_default_view') . '.php');
