<?php
/**
 * All tests for the Kolab_FreeBusy:: package.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Kolab_FreeBusy_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 */
class Horde_Kolab_FreeBusy_AllTests extends Horde_Test_AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Horde Framework - Kolab_FreeBusy');

        $basedir    = dirname(__FILE__);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                require $pathname;

                $class = str_replace(
                    DIRECTORY_SEPARATOR, '_',
                    preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname)
                );
                $suite->addTestSuite('Horde_Kolab_FreeBusy_' . $class);
            }
        }

        return $suite;
    }
}

Horde_Kolab_FreeBusy_AllTests::init('Horde_Kolab_FreeBusy', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Kolab_FreeBusy_AllTests::main') {
    Horde_Kolab_FreeBusy_AllTests::main();
}
