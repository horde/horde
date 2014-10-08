<?php
/**
 * Bootstrap code for PHPUnit tests.
 *
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 * @package  Test
 */
class Horde_Test_Bootstrap
{
    /**
     * Only run bootstrap code once.
     *
     * @var boolean
     */
    private static $_runonce = false;

    /**
     * Bootstrap code for Horde PEAR packages.
     *
     * @param string $dir           Base directory of tests.
     * @param boolean $no_autoload  Don't run default Horde_Test autoload
     *                              tasks.
     */
    public static function bootstrap($dir, $no_autoload = false)
    {
        if (self::$_runonce) {
            return;
        }

        if (!$no_autoload) {
            // Catch strict standards
            error_reporting(E_ALL | E_STRICT);

            // Set up autoload
            $base = $dir;
            while ($base != '/' && basename($base) != 'Horde') {
                $base = dirname($base);
            }
            $base = dirname($base);
            if ($base) {
                set_include_path(
                    $base . PATH_SEPARATOR . $base . '/../lib' . PATH_SEPARATOR . get_include_path()
                );
            }
            require_once 'Horde/Test/Autoload.php';
            Horde_Test_Autoload::init();
        }

        if (file_exists($dir . '/Autoload.php')) {
            require_once $dir . '/Autoload.php';
        }

        self::$_runonce = true;
    }

}
