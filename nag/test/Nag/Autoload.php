<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

$mappings = array('Nag' => dirname(__FILE__) . '/../../lib/');
require_once 'Horde/Test/Autoload.php';

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);

/** Load the basic test definition */
require_once dirname(__FILE__) . '/TestCase.php';
require_once dirname(__FILE__) . '/Unit/Driver/Base.php';
require_once dirname(__FILE__) . '/Unit/Driver/Sql/Base.php';
require_once dirname(__FILE__) . '/Unit/Nag/Base.php';
require_once dirname(__FILE__) . '/Unit/Nag/Sql/Base.php';
require_once dirname(__FILE__) . '/Unit/Form/Task/Base.php';
require_once dirname(__FILE__) . '/Unit/Form/Task/Sql/Base.php';
