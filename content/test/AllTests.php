<?php
/**
 * @package    Rampage_Content
 * @subpackage UnitTests
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Content_AllTests::main');
}

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require 'Horde/Autoloader.php';
require dirname(__FILE__) . '/../lib/Types/Manager.php';
require dirname(__FILE__) . '/../lib/Users/Manager.php';
require dirname(__FILE__) . '/../lib/Objects/Manager.php';
require dirname(__FILE__) . '/../lib/Tagger.php';

/**
 * @package    Rampage_Content
 * @subpackage UnitTests
 */
class Content_AllTests {

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        // Catch strict standards
        error_reporting(E_ALL | E_STRICT);

        // Ensure a default timezone is set.
        date_default_timezone_set('America/New_York');

        // Set up autoload
        if (!spl_autoload_functions()) {
            spl_autoload_register(create_function('$class', '$filename = str_replace(array(\'::\', \'_\'), \'/\', $class); @include_once "$filename.php";'));
        }

        // Build the suite
        $suite = new PHPUnit_Framework_TestSuite('Rampage Components - Content');

        $basedir = dirname(__FILE__);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                require $pathname;

                $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                     preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                $suite->addTestSuite('Content_' . $class);
            }
        }

        return $suite;
    }

}

if (PHPUnit_MAIN_METHOD == 'Content_AllTests::main') {
    Content_AllTests::main();
}
