<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */

require_once 'Horde/Test/Autoload.php';

if (!class_exists('Components')) {
    set_include_path(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR . get_include_path());
}

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);

/** Load the basic test definition */
require_once dirname(__FILE__) . '/StoryTestCase.php';
require_once dirname(__FILE__) . '/TestCase.php';