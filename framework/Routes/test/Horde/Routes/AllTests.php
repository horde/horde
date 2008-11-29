<?php
/**
 * Horde Routes package
 *
 * This package is heavily inspired by the Python "Routes" library
 * by Ben Bangert (http://routes.groovie.org).  Routes is based
 * largely on ideas from Ruby on Rails (http://www.rubyonrails.org).
 *
 * @author  Maintainable Software, LLC. (http://www.maintainable.com)
 * @author  Mike Naberezny <mike@maintainable.com>
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * @package Horde_Routes
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Routes_AllTests::main');
}

error_reporting(E_ALL | E_STRICT);

$lib = dirname(dirname(dirname(dirname(__FILE__)))) . '/lib/Horde/Routes/';
require_once "$lib/Utils.php";
require_once "$lib/Mapper.php";
require_once "$lib/Route.php";
require_once "$lib/Exception.php";

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

/**
 * @package Horde_Routes
 */
class Horde_Routes_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Routes');

        $basedir = dirname(__FILE__);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach(new RecursiveIteratorIterator(
                 new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                require $pathname;

                $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                     preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                $suite->addTestSuite($class);
            }
        }

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Horde_Routes_AllTests::main') {
    Horde_Routes_AllTests::main();
}
