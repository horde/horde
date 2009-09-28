<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

if (!defined('HORE_KOLAB_SERVER_TESTS')) {
    $test_dir = '@test_dir@/Kolab_Server';

    if (substr($test_dir, 0, 1) == '@') {
        /**
         * Assume we are working in development mode and this package resides in
         * 'framework'.
         */
        define('HORE_KOLAB_SERVER_TESTS', dirname(__FILE__) . '/../../../../../Kolab_Server/test');
    } else {
        define('HORE_KOLAB_SERVER_TESTS', $test_dir);
    }

    Horde_Autoloader::addClassPath(HORE_KOLAB_SERVER_TESTS);
}

if (!defined('HORE_KOLAB_AUTH_TESTS')) {
    $test_dir = '@test_dir@/Auth';

    if (substr($test_dir, 0, 1) == '@') {
        /**
         * Assume we are working in development mode and this package resides in
         * 'framework'.
         */
        define('HORE_KOLAB_AUTH_TESTS', dirname(__FILE__) . '/../../..');
    } else {
        define('HORE_KOLAB_AUTH_TESTS', $test_dir);
    }

    Horde_Autoloader::addClassPath(HORE_KOLAB_AUTH_TESTS);
}