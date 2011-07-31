<?php
/**
 * Klutz index script.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('klutz');
require dirname(__FILE__) . '/comics.php';
