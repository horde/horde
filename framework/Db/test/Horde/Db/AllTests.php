<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Db_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';
require_once dirname(__FILE__) . '/Adapter/MissingTest.php';

/* Ensure a default timezone is set. */
date_default_timezone_set('America/New_York');

/**
 * @package    Horde_Db
 * @subpackage UnitTests
 */
class Horde_Db_AllTests extends Horde_Test_AllTests
{
    /**
     * Main entry point for running the suite.
     */
    public static function main($package = null, $file = null)
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        // Catch strict standards
        error_reporting(E_ALL | E_STRICT);

        // Ensure a default timezone is set.
        date_default_timezone_set('America/New_York');

        // Set up autoload
        set_include_path(dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR . get_include_path());
        if (!spl_autoload_functions()) {
            spl_autoload_register(create_function('$class', '$filename = str_replace(array(\'::\', \'_\'), \'/\', $class); @include_once "$filename.php";'));
        }

        // Build the suite
        $suite = new PHPUnit_Framework_TestSuite('Horde Framework - Horde_Db');

        $basedir = dirname(__FILE__);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filename = $file->getFilename();
            $pathname = $file->getPathname();
            $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                 preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));

            if (preg_match('/Suite.php$/', $filename)) {
                require $pathname;
                $suite->addTestSuite('Horde_Db_' . $class);
            } elseif (strpos($class, 'Adapter_') === false && preg_match('/Test.php$/', $filename)) {
                require $pathname;
                $suite->addTestSuite('Horde_Db_' . $class);
            }
        }

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Horde_Db_AllTests::main') {
    Horde_Db_AllTests::main('Horde_Db', __FILE__);
}
