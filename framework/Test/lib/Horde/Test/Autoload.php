<?php
/**
 * Reduced Horde Autoloader for test suites.
 *
 * PHP version 5
 *
 * Copyright 2009-2011 Horde LLC (http://www.horde.org/)
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
$autoloaders = spl_autoload_functions();
if (!empty($autoloaders)) {
    /**
     * Ignore autoloaders which are incapable of loading Horde
     * classes (e.g. PHPUnit >= 3.5.0)
     */
    $autoloaders = array_diff($autoloaders, array('phpunit_autoload'));
}
if (empty($autoloaders)) {
    $mapping = '';
    if (!empty($mappings)) {
        foreach ($mappings as $prefix => $path) {
            $mapping .= 'if (strpos($filename, "/") === false && $filename == "' . $prefix . '") {'
                . '  $filename = "' . $path . '$filename";'
                . '}';
            $mapping .= 'if (substr($filename, 0, ' . strlen($prefix) . ') == "' . $prefix . '") {'
                . '  $filename = substr($filename, ' . strlen($prefix) . ');'
                . '  $filename = "' . $path . '$filename";'
                . '}';
        }
        unset($mappings);
    }
    spl_autoload_register(
        create_function(
            '$class',
            '$filename = str_replace(array(\'::\', \'_\'), \'/\', $class);'
            . $mapping
            . '$err_mask = error_reporting() & ~E_WARNING;'
            . '$oldErrorReporting = error_reporting($err_mask);'
            . 'include "$filename.php";'
            . 'error_reporting($oldErrorReporting);'
        )
    );
}

unset($autoloaders, $mapping);