<?php
/**
 * @category   Horde
 * @package    Ingo
 * @subpackage UnitTests
 * @copyright  2009 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Ingo_AllTests::main');
}

/**
 * Prepare the test setup.
 */
@define('INGO_BASE', dirname(__FILE__) . '/../..');
require_once 'Horde/Autoloader/Default.php';
date_default_timezone_set('Europe/Berlin');

/**
 * @package    Horde_Icalendar
 * @subpackage UnitTests
 */
class Ingo_AllTests extends Horde_Test_AllTests
{
   /**
    * Collect the unit tests of this directory into a new suite.
    *
    * @return PHPUnit_Framework_TestSuite The test suite.
    */
    public static function suite()
    {
        // Catch strict standards
        error_reporting(E_ALL);

        // Set up autoload
        $basedir = dirname(__FILE__);
        $GLOBALS['__autoloader']->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Ingo(?:$|_)/', $basedir . '/../'));

        $suite = new PHPUnit_Framework_TestSuite('Ingo');
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                require $pathname;

                $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                     preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                $suite->addTestSuite('Ingo_' . $class);
            }
        }

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Ingo_AllTests::main') {
    Ingo_AllTests::main('Ingo', __FILE__);
}
