<?php
/**
 * Reduced Horde Autoloader for test suites.
 *
 * PHP version 5
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
class Horde_Test_Autoload
{
    /**
     * Prefix mappings.
     *
     * @var array
     */
    private static $_mappings = array();

    /**
     * Only run init code once.
     *
     * @var boolean
     */
    private static $_runonce = false;

    /**
     * Base autoloader code for Horde PEAR packages.
     */
    public static function init()
    {
        if (self::$_runonce) {
            return;
        }

        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require __DIR__ . '/vendor/autoload.php';
        } else {
            require __DIR__ . '/../../../bundle/vendor/autoload.php';
        }

        spl_autoload_register(
            function($class) {
                $filename = Horde_Test_Autoload::resolve($class);
                $err_mask = error_reporting() & ~E_WARNING;
                $old_err = error_reporting($err_mask);
                include "$filename.php";
                error_reporting($old_err);
            }
        );

        self::$_runonce = true;
    }

    /**
     * Add a prefix to the autoloader.
     *
     * @param string $prefix  Prefix to add.
     * @param string $path    Path to the prefix.
     */
    public static function addPrefix($prefix, $path)
    {
        self::$_mappings[$prefix] = $path;
    }

    /**
     * Resolve classname to a filename.
     *
     * @param string $class  Class name.
     *
     * @return string  Resolved filename.
     */
    public static function resolve($class)
    {
        $filename = str_replace(array('::', '_', '\\'), '/', $class);

        foreach (self::$_mappings as $prefix => $path) {
            if ((strpos($filename, "/") === false) && ($filename == $prefix)) {
                $filename = $path . '/' . $filename;
            }
            if (substr($filename, 0, strlen($prefix)) == $prefix) {
                $filename = $path . substr($filename, strlen($prefix));
            }
        }

        return $filename;
    }

}
