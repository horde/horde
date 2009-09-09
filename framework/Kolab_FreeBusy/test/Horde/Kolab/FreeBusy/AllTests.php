<?php
/**
 * All tests for the Kolab_FreeBusy:: package.
 *
 * $Horde: framework/Kolab_FreeBusy/test/Horde/Kolab/FreeBusy/AllTests.php,v 1.2 2009/01/06 17:49:24 jan Exp $
 *
 * @package Kolab_FreeBusy
 */

/**
 * Define the main method 
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Kolab_FreeBusy_AllTests::main');
}

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

/**
 * Combine the tests for this package.
 *
 * $Horde: framework/Kolab_FreeBusy/test/Horde/Kolab/FreeBusy/AllTests.php,v 1.2 2009/01/06 17:49:24 jan Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @package Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_AllTests {

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Horde Framework - Horde_Kolab_FreeBusy');

        $basedir = dirname(__FILE__);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                require $pathname;

                $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                     preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                $suite->addTestSuite('Horde_Kolab_FreeBusy_' . $class);
            }
        }

        return $suite;
    }

}

if (PHPUnit_MAIN_METHOD == 'Horde_Kolab_FreeBusy_AllTests::main') {
    Horde_Kolab_FreeBusy_AllTests::main();
}
