<?php
/**
 * Horde base test suite
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Test
 * @subpackage UnitTests
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Test_AllTests::main');
}

require_once 'PHPUnit/Autoload.php';

/**
 * @package    Test
 * @subpackage UnitTests
 */
class Horde_Test_AllTests
{
    /** @todo: Use protected properties and LSB with PHP 5.3. */
    private static $_file = __FILE__;
    private static $_package = 'Horde_Test';

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

    /**
     * Initialize the test suite class.
     *
     * @param string $package The name of the package tested by this suite.
     * @param string $file    The path of the AllTests class.
     *
     * @return NULL
     */
    public static function init($package, $file)
    {
        self::$_package = $package;
        self::$_file = $file;
    }

    /**
     * Collect the unit tests of this directory into a new suite.
     *
     * @return PHPUnit_Framework_TestSuite The test suite.
     */
    public static function suite()
    {
        self::setup();

        $suite = new PHPUnit_Framework_TestSuite('Horde Framework - ' . self::$_package);

        $basedir = dirname(self::$_file);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                if (require $pathname) {
                    $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                         preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                    try {
                        $suite->addTestSuite(self::$_package . '_' . $class);
                    } catch (InvalidArgumentException $e) {
                        throw new Horde_Test_Exception(
                            sprintf(
                                'Failed adding test suite "%s" from file "%s": %s',
                                self::$_package . '_' . $class,
                                $pathname,
                                $e->getMessage()
                            )
                        );
                    }
                }
            }
        }

        return $suite;
    }

    /**
     * Basic test suite setup. This includes error checking and autoloading.
     *
     * In the default situation this will set the error reporting to E_ALL |
     * E_STRICT and pull in Horde/Test/Autoload.php as autoloading
     * definition. If there is an Autoload.php alongside the AllTests.php
     * represented by self::$_file, then only this file will be used.
     *
     * @return NULL
     */
    public static function setup()
    {
        set_include_path(dirname(self::$_file) . '/../../../lib' . PATH_SEPARATOR . get_include_path());

        $autoload = dirname(self::$_file) . '/Autoload.php';
        if (!file_exists($autoload)) {
            // Catch strict standards
            error_reporting(E_ALL | E_STRICT);

            // Set up autoload
            require_once 'Horde/Test/Autoload.php';
        } else {
            require_once $autoload;
        }
    }
}
