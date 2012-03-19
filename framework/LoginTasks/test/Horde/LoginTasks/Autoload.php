<?php
/**
 * Setup autoloading for the tests.
 *
 * @category Horde
 * @package  LoginTasks
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=LoginTasks
 */

require_once 'Horde/Test/Autoload.php';

/* Catch strict standards */
error_reporting(E_ALL | E_STRICT);

require_once __DIR__ . '/Stubs.php';
