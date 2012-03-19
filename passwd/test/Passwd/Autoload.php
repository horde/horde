<?php
/**
 * Setup autoloading for the tests.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Passwd
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/gpl GPL
 */

$mappings = array('Passwd' => __DIR__ . '/../../lib/');
require_once 'Horde/Test/Autoload.php';

/* Catch strict standards */
error_reporting(E_ALL | E_STRICT);

/** Load the basic test definition */
require_once __DIR__ . '/TestCase.php';