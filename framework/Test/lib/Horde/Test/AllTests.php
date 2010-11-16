<?php
/**
 * Horde base test suite
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Horde_Test
 * @subpackage UnitTests
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Test_AllTests::main');
}

/**
 * @package    Horde_Test
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
        // Catch strict standards
        error_reporting(E_ALL | E_STRICT);

        // Set up autoload
        $basedir = dirname(self::$_file);
        set_include_path($basedir . '/../../../lib' . PATH_SEPARATOR . get_include_path());
        require_once 'Horde/Test/Autoload.php';

        $suite = new PHPUnit_Framework_TestSuite('Horde Framework - ' . self::$_package);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                if (require $pathname) {
                    $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                         preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                    $suite->addTestSuite(self::$_package . '_' . $class);
                }
            }
        }

        return $suite;
    }

}
