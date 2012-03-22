<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Ansel
 * @subpackage UnitTests
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @license    http://www.horde.org/licenses/gpl GPL-2.0
 * @link       http://www.horde.org/apps/mnemo
 */

$mappings = array('Ansel' => __DIR__ . '/../../lib/');
require_once 'Horde/Test/Autoload.php';

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);

/** Load the basic test definition */
require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/Unit/Base.php';
