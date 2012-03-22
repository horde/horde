<?php
/**
 * Setup autoloading for the tests.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Filter
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

require_once 'Horde/Test/Autoload.php';

/* Catch strict standards */
error_reporting(E_ALL | E_STRICT);

/* Load the basic test definition */
require_once __DIR__ . '/StoryTestCase.php';

/* Load the test helpers */
require_once __DIR__ . '/Helper/AddressFilter.php';
