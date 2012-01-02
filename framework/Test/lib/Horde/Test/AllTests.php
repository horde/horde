<?php
/**
 * Horde base test suite
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Test
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Test_AllTests::main');
}

require_once 'PHPUnit/Autoload.php';

/**
 * Horde base test suite
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Test
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
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
                if (include $pathname) {
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
     * In addition the setup() call will attempt to detect the "lib" directory
     * of the component currently under test and add it to the
     * include_path. This ensures that the component code from the checkout is
     * preferred over whatever else might be available in the default
     * include_path.
     *
     * @return NULL
     */
    public static function setup()
    {
        // Detect component root and add "lib" to the include path.
        for ($dirname = self::$_file, $i = 0;
             $dirname != '/', $i < 5;
             $dirname = dirname($dirname), $i++) {
            if (basename($dirname) == 'test' &&
                file_exists(dirname($dirname) . '/lib')) {
                set_include_path(
                    dirname($dirname) . '/lib' . PATH_SEPARATOR . get_include_path()
                );
                break;
            }
        }

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
