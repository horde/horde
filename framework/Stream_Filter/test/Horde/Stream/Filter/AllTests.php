<?php
/**
 * Horde_Stream_Filter test suite
 *
 * @category   Horde
 * @package    Horde_Stream_Filter
 * @subpackage UnitTests
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Stream_Filter_AllTests::main');
}

if (!spl_autoload_functions()) {
    spl_autoload_register(create_function('$class', '$filename = str_replace(array(\'::\', \'_\'), \'/\', $class); @include_once "$filename.php";'));
}

/**
 * @package    Horde_Stream_Filter
 * @subpackage UnitTests
 */
class Horde_Stream_Filter_AllTests
{
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

        $suite = new PHPUnit_Framework_TestSuite('Horde Framework - Horde_Stream_Filter');

        $basedir = dirname(__FILE__);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                require $pathname;

                $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                     preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                $suite->addTestSuite('Horde_Stream_Filter_' . $class);
            }
        }

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Horde_Stream_Filter_AllTests::main') {
    Horde_Stream_Filter_AllTests::main();
}
