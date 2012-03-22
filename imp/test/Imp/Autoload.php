<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    IMP
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/gpl GPL
 * @link       http://pear.horde.org/index.php?package=Imp
 */

$mappings = array('IMP' => __DIR__ . '/../../lib/');
require_once 'Horde/Test/Autoload.php';

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);
