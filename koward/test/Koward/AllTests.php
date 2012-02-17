<?php
/**
 * All tests for the Koward application.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Koward
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Koward
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Koward_AllTests::main');
}

/**
 * Initialize testing for this application.
 */
require_once 'TestInit.php';

/**
 * Combine the tests for this package.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Koward
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Koward
 */
class Koward_AllTests
{
    /**
     * Main entry point for running the suite.
     *
     * @return NULL
     */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
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

        // Build the suite
        $suite = new PHPUnit_Framework_TestSuite('Koward');

        $basedir = dirname(__FILE__);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                require $pathname;

                $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                     preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                $suite->addTestSuite('Koward_' . $class);
            }
        }

        return $suite;
    }

}

if (PHPUnit_MAIN_METHOD == 'Koward_AllTests::main') {
    Koward_AllTests::main();
}
