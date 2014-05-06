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

/**
 * Horde base test suite
 *
 * Copyright 2009-2014 Horde LLC (http://www.horde.org/)
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
            if ($val == 'test' ||
                $val == implode('_', array_reverse($parts))) {
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
    }

    /**
     * Main entry point for running the suite.
     *
     * @return boolean
     */
    public function run()
    {
        $old_dir = getcwd();
        chdir($this->_dir);
        $old_error = error_reporting();
        $suite = $this->suite();
        $result = PHPUnit_TextUI_TestRunner::run($suite);
        error_reporting($old_error);
        chdir($old_dir);
        return $result;
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
            if ($file->isFile()) {
                $pathname = $file->getPathname();
                if ((preg_match('/Test.php$/', $file->getFilename())) &&
                    (include $pathname)) {
                    $class = str_replace(
                        DIRECTORY_SEPARATOR, '_',
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
        // Detect component root and add "lib" and "test" to the include path.
        $base = $this->_dir;
        while ($base != '/' && basename($base) != 'test') {
            $base = dirname($base);
        }
        if ($base) {
            set_include_path(
                $base . PATH_SEPARATOR . $base . '/../lib' . PATH_SEPARATOR . get_include_path()
            );
        }

        require_once 'Horde/Test/Bootstrap.php';
        Horde_Test_Bootstrap::bootstrap($this->_dir);

        if (file_exists($this->_dir . '/Autoload.php')) {
            require_once $this->_dir . '/Autoload.php';
        }
    }

}
