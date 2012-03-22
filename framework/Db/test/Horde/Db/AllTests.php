<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage UnitTests
 */

/* Define the main method. */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Db_AllTests::main');
}

/* Prepare the test setup. */
require_once 'Horde/Test/AllTests.php';

/* Set up autoload. */
set_include_path(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR . get_include_path());
require_once 'Horde/Test/Autoload.php';
require_once __DIR__ . '/Adapter/MissingTest.php';

/* Ensure a default timezone is set. */
date_default_timezone_set('America/New_York');

/**
 * @package    Db
 * @subpackage UnitTests
 */
class Horde_Db_AllTests extends Horde_Test_AllTests
{
    public static $connFactory;

    /**
     * Main entry point for running the suite.
     */
    public static function main($package = null, $file = null)
    {
        if ($package) {
            self::$_package = $package;
        }
        if ($file) {
            self::$_file = $file;
        }

        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        // Catch strict standards
        error_reporting(E_ALL | E_STRICT);

        // Ensure a default timezone is set.
        date_default_timezone_set('America/New_York');

        // Build the suite
        $suite = new PHPUnit_Framework_TestSuite('Horde Framework - Horde_Db');

        $basedir = __DIR__;
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

Horde_Db_AllTests::init('Horde_Db', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Db_AllTests::main') {
    Horde_Db_AllTests::main();
}
