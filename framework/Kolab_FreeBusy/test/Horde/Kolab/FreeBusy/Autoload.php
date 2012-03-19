<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

require_once 'Horde/Test/Autoload.php';

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);

/** Load stub definitions */
require_once __DIR__ . '/Stub/Provider.php';
require_once __DIR__ . '/Stub/MatchDict.php';
require_once __DIR__ . '/Stub/Object.php';
require_once __DIR__ . '/Stub/Server.php';
require_once __DIR__ . '/Stub/User.php';

/** Load the basic test definition */
require_once __DIR__ . '/TestCase.php';
