<?php
/**
 * Setup autoloading for the tests.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Yaml
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd BSD
 */

require_once 'Horde/Test/Autoload.php';
require_once __DIR__ . '/Helpers.php';

/* Catch strict standards */
error_reporting(E_ALL | E_STRICT);
