<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/turba
 * @license    http://www.horde.org/licenses/apache Apache-like
 */

Horde_Test_Autoload::addPrefix('Turba', __DIR__ . '/../../lib');

/** Load the basic test definition */
require_once __DIR__ . '/TestCase.php';

/** Load stub definitions */
require_once __DIR__ . '/Stub/Tagger.php';
require_once __DIR__ . '/Stub/Types.php';
