<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('whups');

require basename($prefs->getValue('whups_default_view') . '.php');
