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

require_once 'Horde/Test/Autoload.php';

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);

/** Load the basic test definition */
require_once dirname(__FILE__) . '/TestCase.php';

/** Load stub definitions */
require_once dirname(__FILE__) . '/Stub/BooleanDefault.php';
require_once dirname(__FILE__) . '/Stub/BooleanNotEmpty.php';
require_once dirname(__FILE__) . '/Stub/ColorDefault.php';
require_once dirname(__FILE__) . '/Stub/ColorNotEmpty.php';
require_once dirname(__FILE__) . '/Stub/Composite.php';
require_once dirname(__FILE__) . '/Stub/DateTimeDefault.php';
require_once dirname(__FILE__) . '/Stub/DateTimeNotEmpty.php';
require_once dirname(__FILE__) . '/Stub/IntegerDefault.php';
require_once dirname(__FILE__) . '/Stub/IntegerNotEmpty.php';
require_once dirname(__FILE__) . '/Stub/Log.php';
require_once dirname(__FILE__) . '/Stub/Dummy.php';
require_once dirname(__FILE__) . '/Stub/MultipleNotEmpty.php';
require_once dirname(__FILE__) . '/Stub/MultipleDefault.php';
require_once dirname(__FILE__) . '/Stub/StringDefault.php';
require_once dirname(__FILE__) . '/Stub/StringNotEmpty.php';
require_once dirname(__FILE__) . '/Stub/Types.php';
