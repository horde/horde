<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/apache
 * @link       http://www.horde.org/apps/mnemo
 */

Horde_Test_Autoload::addPrefix('Mnemo', __DIR__ . '/../../lib');

/** Load the basic test definition */
require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/Unit/Mnemo/Base.php';
require_once __DIR__ . '/Unit/Mnemo/Sql/Base.php';
