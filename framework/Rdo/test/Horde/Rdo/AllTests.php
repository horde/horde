<?php
/**
 * @package    Horde_Rdo
 * @subpackage UnitTests
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Rdo_AllTests::main');
}

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

class Horde_Rdo_AllTests {

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Horde Framework - Horde_Rdo');

        $basedir = dirname(__FILE__);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                require $pathname;

                $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                     preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                $suite->addTestSuite('Horde_Rdo_' . $class);
            }
        }

        return $suite;
    }

}

if (PHPUnit_MAIN_METHOD == 'Horde_Rdo_AllTests::main') {
    Horde_Rdo_AllTests::main();
}
