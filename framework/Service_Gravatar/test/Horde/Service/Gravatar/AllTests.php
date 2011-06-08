<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Service_Gravatar_AllTests::main');
}

require_once 'PHPUnit/Autoload.php';

class Horde_Service_Gravatar_AllTests
{
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

        // Set up autoload
        $basedir = dirname(__FILE__);
        set_include_path($basedir . '/../../../../lib' . PATH_SEPARATOR . get_include_path());

        $suite = new PHPUnit_Framework_TestSuite('Horde Framework - Service_Gravatar');
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                if (require $pathname) {
                    $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                         preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                    try {
                        $suite->addTestSuite('Horde_Service_Gravatar_' . $class);
                    } catch (InvalidArgumentException $e) {
                        throw new Exception(
                            sprintf(
                                'Failed adding test suite "%s" from file "%s": %s',
                                'Horde_Service_Gravatar_' . $class,
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
}

if (PHPUnit_MAIN_METHOD == 'Horde_Service_Gravatar_AllTests::main') {
    Horde_Service_Gravatar_AllTests::main();
}
