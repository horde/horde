<?php
/**
 * Bootstrap code for PHPUnit tests.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
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
    static public function bootstrap($dir, $no_autoload = false)
    {
        if (self::$_runonce) {
            return;
        }

        if (!$no_autoload) {
            // Catch strict standards
            error_reporting(E_ALL | E_STRICT);

            // Set up autoload
            require_once 'Horde/Test/Autoload.php';
            Horde_Test_Autoload::init();
        }

        $autoload = $dir . DIRECTORY_SEPARATOR . 'Autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        self::$_runonce = true;
    }

}
