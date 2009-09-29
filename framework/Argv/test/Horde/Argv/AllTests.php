<?php
/**
 * Horde_Argv test suite
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Horde_Argv
 * @subpackage UnitTests
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Argv_AllTests::main');
}

if (!function_exists('_')) {
    function _($t) {
        return $t;
    }
}

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

/**
 * @package    Horde_Argv
 * @subpackage UnitTests
 */
class Horde_Argv_AllTests {

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        // Catch strict standards
        error_reporting(E_ALL | E_STRICT);

        // Set up autoload
        set_include_path(dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR . get_include_path());
        if (!spl_autoload_functions()) {
            spl_autoload_register(create_function('$class', '$filename = str_replace(array(\'::\', \'_\'), \'/\', $class); include "$filename.php";'));
        }

        // Test base classes and helper objects
        require_once dirname(__FILE__) . '/TestCase.php';
        require_once dirname(__FILE__) . '/ConflictTestCase.php';
        require_once dirname(__FILE__) . '/InterceptedException.php';
        require_once dirname(__FILE__) . '/InterceptingParser.php';

        $suite = new PHPUnit_Framework_TestSuite('Horde Framework - Horde_Argv');

        $basedir = dirname(__FILE__);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                require $pathname;

                $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                     preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                $suite->addTestSuite('Horde_Argv_' . $class);
            }
        }

        return $suite;
    }

}

if (PHPUnit_MAIN_METHOD == 'Horde_Argv_AllTests::main') {
    Horde_Argv_AllTests::main();
}
