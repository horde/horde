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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

require_once 'Horde/Test/Autoload.php';

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);

/** Load stub definitions */
require_once dirname(__FILE__) . '/Stub/Object.php';
require_once dirname(__FILE__) . '/Stub/Server.php';
require_once dirname(__FILE__) . '/Stub/User.php';

/** Load the basic test definition */
require_once dirname(__FILE__) . '/TestCase.php';
