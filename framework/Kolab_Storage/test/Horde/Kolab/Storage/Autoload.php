<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

require_once 'Horde/Test/Autoload.php';

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);

/** Load the basic test definition */
require_once __DIR__ . '/TestCase.php';

/** Load stub definitions */
require_once __DIR__ . '/Stub/DataQuery.php';
require_once __DIR__ . '/Stub/Driver.php';
