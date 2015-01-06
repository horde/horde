<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Components
 */

if (!class_exists('Components')) {
    set_include_path(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR . get_include_path());
}

/** Load the basic test definition */
require_once __DIR__ . '/StoryTestCase.php';
require_once __DIR__ . '/TestCase.php';

/** Load stub definitions */
require_once __DIR__ . '/Stub/Config.php';
require_once __DIR__ . '/Stub/Output.php';
