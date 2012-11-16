<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/** Load the basic test definition */
require_once __DIR__ . '/TestCase.php';

/** Load stub definitions */
require_once __DIR__ . '/Stub/BooleanDefault.php';
require_once __DIR__ . '/Stub/BooleanNotEmpty.php';
require_once __DIR__ . '/Stub/ColorDefault.php';
require_once __DIR__ . '/Stub/ColorNotEmpty.php';
require_once __DIR__ . '/Stub/Composite.php';
require_once __DIR__ . '/Stub/DateTimeDefault.php';
require_once __DIR__ . '/Stub/DateTimeNotEmpty.php';
require_once __DIR__ . '/Stub/IntegerDefault.php';
require_once __DIR__ . '/Stub/IntegerNotEmpty.php';
require_once __DIR__ . '/Stub/Log.php';
require_once __DIR__ . '/Stub/Dummy.php';
require_once __DIR__ . '/Stub/MultipleNotEmpty.php';
require_once __DIR__ . '/Stub/MultipleDefault.php';
require_once __DIR__ . '/Stub/StringDefault.php';
require_once __DIR__ . '/Stub/StringNotEmpty.php';
require_once __DIR__ . '/Stub/Types.php';
