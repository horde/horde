<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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

if (!spl_autoload_functions()) {
    spl_autoload_register(
        create_function(
            '$class', 
            '$filename = str_replace(array(\'::\', \'_\'), \'/\', $class);'
            . '$err_mask = E_ALL ^ E_WARNING;'
            . '$oldErrorReporting = error_reporting($err_mask);'
            . 'include "$filename.php";'
            . 'error_reporting($oldErrorReporting);'
        )
    );
}

if (!class_exists('Components')) {
    set_include_path(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR . get_include_path());
}

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);


/** Load the basic test definition */
require_once dirname(__FILE__) . '/StoryTestCase.php';

/** Load stubs */
require_once dirname(__FILE__) . '/Stub/Cli.php';
require_once dirname(__FILE__) . '/Stub/Parser.php';
