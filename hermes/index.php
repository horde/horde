<?php
/**
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

// Will redirect to login page if not authenticated.
require_once dirname(__FILE__) . '/lib/Application.php';
$hermes = Horde_Registry::appInit('hermes');
require HERMES_BASE . '/time.php';
