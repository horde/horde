<?php
/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @category Horde
 * @package  Horde_Log
 * @subpackage UnitTests
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Log_AllTests::main');
}

if (!spl_autoload_functions()) {
    spl_autoload_register(create_function('$class', '$filename = str_replace(array(\'::\', \'_\'), \'/\', $class); @include_once "$filename.php";'));
}

/**
 * @category Horde
 * @package  Horde_Log
 * @subpackage UnitTests
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
class Horde_Log_AllTests
{

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        set_include_path(dirname(__FILE__) . '/../../../lib' . PATH_SEPARATOR . get_include_path());
        $suite = new PHPUnit_Framework_TestSuite('Horde_Log');

        $basedir = dirname(__FILE__);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                require $pathname;

                $class = 'Horde_Log_' . str_replace(DIRECTORY_SEPARATOR, '_',
                                                    preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                $suite->addTestSuite($class);
            }
        }

        return $suite;
    }

}

if (PHPUnit_MAIN_METHOD == 'Horde_Log_AllTests::main') {
    Horde_Log_AllTests::main();
}
