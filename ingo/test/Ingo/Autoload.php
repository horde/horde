<?php
/**
 * Setup autoloading for the tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @ignore
 * @license    http://www.horde.org/licenses/apache ASL
 * @package    Ingo
 * @subpackage UnitTests
 */

Horde_Test_Autoload::addPrefix('Ingo', __DIR__ . '/../../lib');

require_once 'Stub/Storage/Mock.php';
require_once 'Stub/Storage/Vacation.php';
require_once 'Unit/TestBase.php';
