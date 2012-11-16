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
    private $_dir;
    private $_package;

    /**
     * Create a Horde_Test_AllTests object.
     *
     * @param string $file  Filename of the AllTests.php script.
     *
     * @return Horde_Test_AllTests  Test object.
     */
    static public function init($file)
    {
        $file = dirname($file);

        $parts = array();
        foreach (array_reverse(explode(DIRECTORY_SEPARATOR, $file)) as $val) {
            if ($val == 'test') {
                break;
            }
            $parts[] = $val;
        }

        return new self(
            implode('_', array_reverse($parts)),
            $file
        );
    }

    /**
     * Constructor.
     *
     * @param string $package  The name of the package tested by this suite.
     * @param string $dir      The path of the AllTests class.
     */
    public function __construct($package, $dir)
    {
        $this->_package = $package;
        $this->_dir = $dir;

        chdir($dir);
    }

    /**
     *
     * Main entry point for running the suite.
     */
    public function run()
    {
        PHPUnit_TextUI_TestRunner::run($this->suite());
    }

    /**
     * Collect the unit tests of this directory into a new suite.
     *
     * @return PHPUnit_Framework_TestSuite The test suite.
     */
    public function suite()
    {
        $this->setup();

        $suite = new PHPUnit_Framework_TestSuite('Horde Framework - ' . $this->_package);

        $baseregexp = preg_quote($this->_dir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->_dir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                if (include $pathname) {
                    $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                         preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                    try {
                        $suite->addTestSuite($this->_package . '_' . $class);
                    } catch (InvalidArgumentException $e) {
                        throw new Horde_Test_Exception(
                            sprintf(
                                'Failed adding test suite "%s" from file "%s": %s',
                                $this->_package . '_' . $class,
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
     * definition. If there is an Autoload.php in $_dir, then only this file
     * will be used.
     *
     * In addition the setup() call will attempt to detect the "lib" directory
     * of the component currently under test and add it to the
     * include_path. This ensures that the component code from the checkout is
     * preferred over whatever else might be available in the default
     * include_path.
     */
    public function setup()
    {
        // Detect component root and add "lib" to the include path.
        for ($dirname = $this->_dir, $i = 0;
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

        require_once 'Horde/Test/Bootstrap.php';
        Horde_Test_Bootstrap::bootstrap($this->_dir);
    }

}
