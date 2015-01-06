<?php
/**
 * Setup autoloading for the tests.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Sesha
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/gpl GPL
 */

Horde_Test_Autoload::addPrefix('Sesha', __DIR__ . '/../../lib');

/** Load the basic test definition */
require_once __DIR__ . '/TestCase.php';
